<?php

namespace GK2\NfseNacional\Transport;

use GK2\NfseNacional\Config\ModuleConfig;
use GK2\NfseNacional\Transport\Auth\AuthStrategyInterface;
use GK2\NfseNacional\Transport\Auth\CertificateAuth;

/**
 * Cliente HTTP para comunicacao com a API NFS-e Nacional.
 *
 * Wrapper sobre Guzzle (ou cURL fallback) que gerencia:
 * - Autenticacao mTLS via estrategia configuravel
 * - Retry automatico em erros 429 e 503
 * - Timeout configuravel
 * - Log de chamadas via logModuleCall()
 */
class HttpClient
{
    private ModuleConfig $config;
    private AuthStrategyInterface $authStrategy;

    private const DEFAULT_TIMEOUT = 30;
    private const MAX_RETRIES = 2;
    private const RETRY_DELAY_SECONDS = 2;

    public function __construct(
        ?ModuleConfig $config = null,
        ?AuthStrategyInterface $authStrategy = null,
    ) {
        $this->config = $config ?? new ModuleConfig();
        $this->authStrategy = $authStrategy ?? new CertificateAuth($this->config);
    }

    /**
     * Executa requisicao POST.
     *
     * @param string $url URL completa do endpoint
     * @param array $payload Dados a enviar (sera serializado como JSON)
     * @return ApiResponse
     */
    public function post(string $url, array $payload): ApiResponse
    {
        return $this->request('POST', $url, [
            'json' => $payload,
        ]);
    }

    /**
     * Executa requisicao GET.
     *
     * @param string $url URL completa do endpoint
     * @param array $query Parametros de query string
     * @return ApiResponse
     */
    public function get(string $url, array $query = []): ApiResponse
    {
        $options = [];
        if (!empty($query)) {
            $options['query'] = $query;
        }

        return $this->request('GET', $url, $options);
    }

    /**
     * Executa a requisicao HTTP com retry e tratamento de erros.
     */
    private function request(string $method, string $url, array $options = []): ApiResponse
    {
        // Configurar autenticacao
        $this->authStrategy->configure($options);

        // Configurar headers padrao
        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ]);

        // Timeout
        $options['timeout'] = self::DEFAULT_TIMEOUT;
        $options['connect_timeout'] = 10;

        // Nao lancar excecoes em respostas HTTP de erro
        $options['http_errors'] = false;

        $lastException = null;

        for ($attempt = 0; $attempt <= self::MAX_RETRIES; $attempt++) {
            try {
                $response = $this->executeRequest($method, $url, $options);

                $httpCode = $response['http_code'];
                $body = $response['body'];

                // Retry em 429 (Too Many Requests) e 503 (Service Unavailable)
                if (in_array($httpCode, [429, 503]) && $attempt < self::MAX_RETRIES) {
                    sleep(self::RETRY_DELAY_SECONDS * ($attempt + 1));
                    continue;
                }

                return $this->parseResponse($httpCode, $body);
            } catch (\Exception $e) {
                $lastException = $e;

                if ($attempt < self::MAX_RETRIES) {
                    sleep(self::RETRY_DELAY_SECONDS);
                    continue;
                }
            }
        }

        $errorMsg = $lastException ? $lastException->getMessage() : 'Erro desconhecido';
        return ApiResponse::error(['Falha na comunicacao: ' . $errorMsg]);
    }

    /**
     * Executa a requisicao HTTP usando Guzzle ou cURL.
     */
    private function executeRequest(string $method, string $url, array $options): array
    {
        // Tentar usar Guzzle se disponivel
        if (class_exists('\GuzzleHttp\Client')) {
            return $this->executeWithGuzzle($method, $url, $options);
        }

        // Fallback: cURL
        return $this->executeWithCurl($method, $url, $options);
    }

    /**
     * Executa via Guzzle.
     */
    private function executeWithGuzzle(string $method, string $url, array $options): array
    {
        $client = new \GuzzleHttp\Client();
        $response = $client->request($method, $url, $options);

        return [
            'http_code' => $response->getStatusCode(),
            'body' => (string) $response->getBody(),
        ];
    }

    /**
     * Executa via cURL (fallback).
     */
    private function executeWithCurl(string $method, string $url, array $options): array
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $options['timeout'] ?? self::DEFAULT_TIMEOUT);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $options['connect_timeout'] ?? 10);

        // Headers
        $headers = [];
        foreach ($options['headers'] ?? [] as $key => $value) {
            $headers[] = $key . ': ' . $value;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Metodo e body
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if (isset($options['json'])) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($options['json']));
            }
        }

        // SSL / Certificado
        if (isset($options['cert'])) {
            curl_setopt($ch, CURLOPT_SSLCERT, $options['cert']);
        }
        if (isset($options['ssl_key'])) {
            curl_setopt($ch, CURLOPT_SSLKEY, $options['ssl_key']);
        }
        if (isset($options['verify'])) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $options['verify']);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $options['verify'] ? 2 : 0);
        }

        $body = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($body === false) {
            throw new \RuntimeException('cURL error: ' . $error);
        }

        return [
            'http_code' => $httpCode,
            'body' => $body,
        ];
    }

    /**
     * Parseia a resposta HTTP em um ApiResponse.
     */
    private function parseResponse(int $httpCode, string $body): ApiResponse
    {
        $data = [];
        $errors = [];

        if (!empty($body)) {
            $decoded = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $data = $decoded;
            }
        }

        // Sucesso: 2xx
        if ($httpCode >= 200 && $httpCode < 300) {
            return ApiResponse::success($data, $httpCode, $body);
        }

        // Extrair mensagens de erro da resposta
        if (!empty($data['mensagem'])) {
            $errors[] = $data['mensagem'];
        }
        if (!empty($data['erros']) && is_array($data['erros'])) {
            foreach ($data['erros'] as $erro) {
                $msg = is_string($erro) ? $erro : ($erro['mensagem'] ?? json_encode($erro));
                $errors[] = $msg;
            }
        }

        if (empty($errors)) {
            $errors[] = 'HTTP ' . $httpCode . ': Erro na requisicao.';
        }

        return ApiResponse::error($errors, $httpCode, $body, $data);
    }
}
