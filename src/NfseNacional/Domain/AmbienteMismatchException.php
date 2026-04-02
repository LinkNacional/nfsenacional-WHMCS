<?php

namespace GK2\NfseNacional\Domain;

/**
 * Lancada quando uma operacao tenta cruzar ambientes (producao x homologacao).
 */
class AmbienteMismatchException extends \RuntimeException
{
}
