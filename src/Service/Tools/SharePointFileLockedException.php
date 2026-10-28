<?php

namespace App\Service\Tools;

/** Dépôt SharePoint refusé parce que le fichier à remplacer est verrouillé (HTTP 409 / 423). */
class SharePointFileLockedException extends \RuntimeException
{
}
