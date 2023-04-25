<?php

namespace App\Repositories;

use Illuminate\Support\Str;

class DomainRepository
{

    public function isValisDomain(String $domain, String $extension): bool
    {
        if (strlen($domain) > 150) return false;

        if (!(!preg_match('/[^a-z0-9#\\-$]/', $domain))) return false;

        if (substr($extension, 0, 3) != "iol") return false;

        if (strlen($extension) > 3) {
            if (substr($extension, 0, 4) != "iol.") return false;

            if (!(!preg_match('/[^a-z.\\-$]/', $domain))) return false;

            if (strlen($extension) > 6) return false;

            if (!str_contains($extension, '.')) return false;
        }

        return true;
    }
}
