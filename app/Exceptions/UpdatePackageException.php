<?php

namespace App\Exceptions;

/**
 * Odrzucenie paczki aktualizacji z czytelnym powodem do pokazania
 * administratorowi — patrz UpdateService::validatePackage()/apply().
 */
class UpdatePackageException extends \RuntimeException
{
}
