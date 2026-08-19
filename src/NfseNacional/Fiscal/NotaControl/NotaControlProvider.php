<?php

namespace GK2\NfseNacional\Fiscal\NotaControl;

use GK2\NfseNacional\Config\ModuleConfig;
use GK2\NfseNacional\Domain\AmbienteGuard;
use GK2\NfseNacional\Domain\Enum\Ambiente;
use GK2\NfseNacional\Fiscal\ProviderInterface;
use GK2\NfseNacional\Transport\ApiResponse;
use GK2\NfseNacional\Transport\Auth\CertificateAuth;

/**
 * Provider para emissores baseados na plataforma Nota Control / ISS.net Online.
 *
 * Exemplos de municípios: Ribeirão Preto/SP e outros conveniados.
 *
 * Protocolo: SOAP 1.1 sobre HTTPS com mTLS (certificado de cliente) + XMLDSIG.
 * Namespace: http://www.sped.fazenda.gov.br/nfse (mesmo da Sefin Nacional).
 *
 * Referência: Manual de Integração Webservice v1.01 (Nota Control, ago/2026).
 */
class NotaControlProvider implements ProviderInterface
{
    private ModuleConfig $config;
    private AmbienteGuard $guard;
    private Ambiente $ambiente;
    private CertificateAuth $certificateAuth;

    private const BASE_URL = 'https://nfse.issnetonline.com.br/wsnfsenacional';

    // ─── SOAP Envelope (template estático) ─────────────────────────

    private const SOAP_ENVELOPE = <<<'XML'
<?xml version="1.0" encoding="utf-8"?>
<soap:Envelope xmlns:soap="http://schemas.xmlsoap.org/soap/envelope/">
  <soap:Header>
    <cabecalho versao="1.01" xmlns="http://www.sped.fazenda.gov.br/nfse">
      <versaoDados>1.01</versaoDados>
    </cabecalho>
  </soap:Header>
  <soap:Body>
    {body}
  </soap:Body>
</soap:Envelope>
XML;

    // ─── Construtor ────────────────────────────────────────────────

    public function __construct(
        ?ModuleConfig $config = null,
        ?AmbienteGuard $guard = null,
    ) {
        $this->config = $config ?? new ModuleConfig();
        $this->guard = $guard ?? AmbienteGuard::getInstance($this->config);
        $this->ambiente = $this->guard->getAmbiente();
        $this->certificateAuth = new CertificateAuth($this->config);
    }

    // ═══ ProviderInterface ══════════════════════════════════════════

    public function emitirDps(string $dpsXml): ApiResponse
    {
        $inner = '<GerarNfseEnvio>' . $this->stripXmlDeclaration($dpsXml) . '</GerarNfseEnvio>';
        $body = $this->wrapSoapMethod('GerarNfse', $inner);

        return $this->send('GerarNfse', $body, function (\DOMDocument $dom): ApiResponse {
            return $this->parseEmitirResposta($dom);
        });
    }

    public function consultarNfse(string $chaveAcesso): ApiResponse
    {
        $inner = '<ConsultarNfseDpsEnvio><ChaveAcesso>' . $chaveAcesso . '</ChaveAcesso></ConsultarNfseDpsEnvio>';
        $body = $this->wrapSoapMethod('ConsultarNfseDps', $inner);

        return $this->send('ConsultarNfseDps', $body, function (\DOMDocument $dom): ApiResponse {
            return $this->parseConsultarResposta($dom);
        });
    }

    public function consultarPorProtocolo(string $protocolo): ApiResponse
    {
        $inner = '<ConsultarLoteDpsEnvio><Protocolo>' . $protocolo . '</Protocolo></ConsultarLoteDpsEnvio>';
        $body = $this->wrapSoapMethod('ConsultarLoteDps', $inner);

        return $this->send('ConsultarLoteDps', $body, function (\DOMDocument $dom): ApiResponse {
            return $this->parseConsultarResposta($dom);
        });
    }

    public function cancelar(string $chaveAcesso, string $eventoXml): ApiResponse
    {
        $inner = '<CancelarNfseEnvio>' . $this->stripXmlDeclaration($eventoXml) . '</CancelarNfseEnvio>';
        $body = $this->wrapSoapMethod('CancelarNfse', $inner);

        return $this->send('CancelarNfse', $body, function (\DOMDocument $dom): ApiResponse {
            return $this->parseCancelarResposta($dom);
        });
    }

    public function obterDanfse(string $chaveAcesso): ApiResponse
    {
        $inner = '<ConsultarUrlNfseEnvio><ChaveAcesso>' . $chaveAcesso . '</ChaveAcesso></ConsultarUrlNfseEnvio>';
        $body = $this->wrapSoapMethod('ConsultarUrlNfse', $inner);

        return $this->send('ConsultarUrlNfse', $body, function (\DOMDocument $dom): ApiResponse {
            $url = $this->extractTagValue($dom, 'UrlVisualizacaoNfseNacional');
            if (empty($url)) {
                return ApiResponse::error(['URL DANFSe nao encontrada na resposta.']);
            }
            return ApiResponse::success(['url' => $url]);
        });
    }

    public function obterXml(string $chaveAcesso): ApiResponse
    {
        return $this->consultarNfse($chaveAcesso);
    }

    public function getDanfseUrl(string $chaveAcesso): string
    {
        return $this->getBaseUrl() . '?chave=' . urlencode($chaveAcesso) . '&tipo=danfse';
    }

    public function getXmlUrl(string $chaveAcesso): string
    {
        return $this->getBaseUrl() . '?chave=' . urlencode($chaveAcesso) . '&tipo=xml';
    }

    // ═══ Transporte SOAP ═══════════════════════════════════════════

    /**
     * Envia uma requisição SOAP e parseia a resposta.
     *
     * @param string $soapAction Nome do método SOAP (ex: 'GerarNfse')
     * @param string $body       XML do corpo (sem envelope)
     * @param callable $parser   Função que recebe DOMDocument e retorna ApiResponse
     */
    private function send(string $soapAction, string $body, callable $parser): ApiResponse
    {
        $envelope = str_replace('{body}', $body, self::SOAP_ENVELOPE);

        $url = $this->getBaseUrl() . '/nfse.asmx';

        // Log da requisição completa (envelope SOAP) para diagnóstico
        logModuleCall('nfsenacional', 'NotaControl-' . $soapAction . '-Requisicao', [
            'url' => $url,
        ], mb_substr($envelope, 0, 8000));

        // Certificado de cliente (mTLS) — exigido pela Nota Control
        [$certPem, $keyPem] = $this->certificateAuth->getPemPaths();

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $envelope,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: text/xml; charset=utf-8',
                'SOAPAction: ' . $soapAction,
            ],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);

        // mTLS: certificado de cliente (autenticação mútua)
        if ($certPem !== null && $keyPem !== null) {
            curl_setopt($ch, CURLOPT_SSLCERT, $certPem);
            curl_setopt($ch, CURLOPT_SSLKEY, $keyPem);
        }

        $rawBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        // Log da resposta crua para diagnóstico (sempre, com limite de tamanho)
        logModuleCall('nfsenacional', 'NotaControl-' . $soapAction . '-Resposta', [
            'http_code' => $httpCode,
        ], mb_substr((string) $rawBody, 0, 4000));

        if ($rawBody === false || !empty($error)) {
            return ApiResponse::error(['Falha na comunicacao: ' . ($error ?: 'Resposta vazia')]);
        }

        if ($httpCode < 200 || $httpCode >= 300) {
            return ApiResponse::error(['HTTP ' . $httpCode . ': ' . mb_substr(strip_tags($rawBody), 0, 300)]);
        }

        return $this->parseSoapBody($rawBody, $parser);
    }

    /**
     * Extrai o corpo da resposta SOAP e aplica o parser.
     */
    private function parseSoapBody(string $rawXml, callable $parser): ApiResponse
    {
        $dom = new \DOMDocument();
        $dom->loadXML($rawXml, LIBXML_NOERROR | LIBXML_NOWARNING);

        // Extrair <soap:Body> → primeiro filho
        $bodyNodes = $dom->getElementsByTagNameNS('http://schemas.xmlsoap.org/soap/envelope/', 'Body');
        if ($bodyNodes->length === 0) {
            // Fallback: tentar parse direto (pode vir sem envelope em alguns casos)
            return $parser($dom);
        }

        $bodyNode = $bodyNodes->item(0);

        // Encontrar o PRIMEIRO FILHO ELEMENTO (ignora whitespace/text nodes)
        $responseNode = null;
        foreach ($bodyNode->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $responseNode = $child;
                break;
            }
        }

        // Criar um DOMDocument só com o nó de resposta
        $responseDom = new \DOMDocument();
        if ($responseNode !== null) {
            $imported = $responseDom->importNode($responseNode, true);
            $responseDom->appendChild($imported);
        }

        return $parser($responseDom);
    }

    // ═══ Parsers de resposta ═══════════════════════════════════════

    /**
     * Parseia a resposta de GerarNfse.
     *
     * Sucesso: <GerarNfseResposta><ListaNfse><CompNfse><Nfse>
     * Erro:    <GerarNfseResposta><ListaMensagemRetorno>
     */
    private function parseEmitirResposta(\DOMDocument $dom): ApiResponse
    {
        // Verificar mensagens de erro
        $erros = $this->extractMensagensRetorno($dom);
        if (!empty($erros)) {
            \LogActivity('NFS-e Nacional [NotaControl] Erro emissao: ' . implode('; ', $erros));
            return ApiResponse::error($erros, 200, $dom->saveXML());
        }

        // Extrair chave de acesso do Id do infNFSe
        $infNfseNodes = $dom->getElementsByTagName('infNFSe');
        $chaveAcesso = '';
        if ($infNfseNodes->length > 0) {
            $chaveAcesso = $infNfseNodes->item(0)->getAttribute('Id');
        }

        // Extrair XML completo da NFS-e
        $nfseNodes = $dom->getElementsByTagName('Nfse');
        $nfseXml = '';
        if ($nfseNodes->length > 0) {
            $nfseXml = $dom->saveXML($nfseNodes->item(0));
        }

        if (empty($chaveAcesso)) {
            return ApiResponse::error(['Chave de acesso nao encontrada na resposta.']);
        }

        // GZip + base64 para compatibilidade com ApiResponse do NacionalProvider
        $nfseXmlGZipB64 = base64_encode(gzencode($nfseXml, 9));

        return ApiResponse::success([
            'chaveAcesso'    => $chaveAcesso,
            'nfseXmlGZipB64' => $nfseXmlGZipB64,
        ], 200, $dom->saveXML());
    }

    /**
     * Parseia resposta de consulta (ConsultarNfseDps / ConsultarLoteDps).
     */
    private function parseConsultarResposta(\DOMDocument $dom): ApiResponse
    {
        $erros = $this->extractMensagensRetorno($dom);
        if (!empty($erros)) {
            return ApiResponse::error($erros, 200, $dom->saveXML());
        }

        $nfseXml = '';
        $nfseNodes = $dom->getElementsByTagName('Nfse');
        if ($nfseNodes->length > 0) {
            $nfseXml = $dom->saveXML($nfseNodes->item(0));
        }

        $chaveAcesso = '';
        $infNfseNodes = $dom->getElementsByTagName('infNFSe');
        if ($infNfseNodes->length > 0) {
            $chaveAcesso = $infNfseNodes->item(0)->getAttribute('Id');
        }

        return ApiResponse::success([
            'chaveAcesso'    => $chaveAcesso,
            'nfseXmlGZipB64' => $nfseXml ? base64_encode(gzencode($nfseXml, 9)) : '',
        ], 200, $dom->saveXML());
    }

    /**
     * Parseia resposta de cancelamento (CancelarNfse).
     */
    private function parseCancelarResposta(\DOMDocument $dom): ApiResponse
    {
        $erros = $this->extractMensagensRetorno($dom);
        if (!empty($erros)) {
            return ApiResponse::error($erros, 200, $dom->saveXML());
        }

        return ApiResponse::success(['cancelado' => true], 200, $dom->saveXML());
    }

    // ═══ Helpers ════════════════════════════════════════════════════

    /**
     * Extrai mensagens de erro do bloco ListaMensagemRetorno.
     *
     * @return string[]
     */
    private function extractMensagensRetorno(\DOMDocument $dom): array
    {
        $erros = [];
        $mensagens = $dom->getElementsByTagName('MensagemRetorno');

        foreach ($mensagens as $msg) {
            $codigo = '';
            $descricao = '';

            foreach ($msg->childNodes as $child) {
                if ($child->nodeName === 'Codigo') {
                    $codigo = trim($child->textContent);
                }
                if ($child->nodeName === 'Descricao') {
                    $descricao = trim($child->textContent);
                }
            }

            $erros[] = ($codigo ? '[' . $codigo . '] ' : '') . ($descricao ?: 'Erro desconhecido');
        }

        return $erros;
    }

    /**
     * Envolve o XML interno no elemento do método SOAP (wrapper do nome do método).
     *
     * ASP.NET .asmx exige que o elemento raiz do <soap:Body> seja o nome do
     * método (ex: <GerarNfse>), com a mensagem de entrada (*Envio) aninhada.
     */
    private function wrapSoapMethod(string $method, string $innerXml): string
    {
        return '<' . $method . ' xmlns="http://www.sped.fazenda.gov.br/nfse">'
             . $innerXml
             . '</' . $method . '>';
    }

    /**
     * Remove a declaração XML (<?xml ...?>) de um documento.
     *
     * Necessário porque o XML assinado gerado por DOMDocument::saveXML()
     * inclui a declaração, mas quando embutido dentro do corpo SOAP ela
     * gera uma segunda declaração inválida no meio do documento.
     */
    private function stripXmlDeclaration(string $xml): string
    {
        return preg_replace('/^\s*<\?xml[^?]*\?>\s*/i', '', $xml) ?? $xml;
    }

    /**
     * Extrai o valor de uma tag pelo nome local.
     */
    private function extractTagValue(\DOMDocument $dom, string $tagName): string
    {
        $nodes = $dom->getElementsByTagName($tagName);
        if ($nodes->length > 0) {
            return trim($nodes->item(0)->textContent);
        }
        return '';
    }

    /**
     * Retorna a URL base do serviço conforme ambiente.
     */
    private function getBaseUrl(): string
    {
        $suffix = $this->ambiente->isProducao() ? '' : '/homologacao';
        return self::BASE_URL . $suffix;
    }
}
