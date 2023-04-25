<?php

namespace App\Http\Controllers;

use Spatie\Crypto\Rsa\KeyPair;
use Spatie\Crypto\Rsa\PublicKey;
use Spatie\Crypto\Rsa\PrivateKey;

class AccountController extends Controller
{
    public function createAccount(KeyPair $keyPair)
    {

        [$privateKey, $publicKey] = $keyPair->generate();
        // dd($privateKey);
        // $pv = `-----BEGIN RSA PRIVATE KEY----- MIICWwIBAAKBgQDEVJPNTlxvwlHKGJTCZI0zXk7UO5Zca22vYW+H2RuaVEZL3EJD eXpS9HLMtsH0oZ975nZygVu3Wq0q7Ts/9aLg1g+L+iDAPihh1O0Zy8YdsxKRiPif 1RAZczbe7vpTNJiEkAQcUhiVUfo1SsQBZhA9vHYct03jfmK0WIR7jCGc6QIDAQAB AoGADEO0T/j7MUgUT+mlMVeSsojLp9ppFDDp0zDlBLl9TfDBy29g/FIUYCZBHaS1 DREjo1vMPm7yScvyUWIsd1xi5kiid9BZGatr4M87SbfvCM566KHzyPuxfwIDVmat HZjVoByx0p4jjtzydj0a2Ue4HrMS92pLcug21MMRepvoJPkCQQDxk0pCyjb/wlC6 0OhuY+CwCUPC9ueb0EKaa+3D0PbewwdrEglAULpJ7mq2O0jcaK1g/SL/4U5TRFur KVNhd29LAkEA0A2st6fzI0np43PNHU4uqQKbIeaF20xtM3C/RLRJVEG7h59zvnqh WMCuN+u+wVgfukosCYkFfoz24r35rq6gGwJBAOoGt31GqstQcqbYdSNkDKpc/1Ej ojI3Kh6xChCWImKha1FVkrLEwgOXPbe2bRphKf14nWxn11ohMfD5wvX8H0sCPzWj UOQIaKawZ5w8PqVPgrgP8+7Jf7kJ+x1H9AfCcyVFzRMqCK0S8IXxJvlDzeoKziM4 /CF5MfFuZjgOwlBGtQJAXK2y3TiA1Ppo/IuUFJwTzrfD+qsk22uyrFj9M8QSlkiJ jvxg11S70d/2MWZ3hPklMcNVARHz6l1bj/2KKBlZZA== -----END RSA PRIVATE KEY-----`;

        return $this->sign($privateKey);

        return [
            'private' => ($privateKey),
            'public' => base64_encode($publicKey),
        ];
    }

    public function sign($privateKey)
    {
        return $signature = (new PrivateKey($privateKey))->sign('amy message');
    }

    // public function verify($publicKey, $message, $sign)
    // {
    //     return (new PublicKey($publicKey))->verify('amy message', $sign);
    // }
    public function encryptToOtherAccount($publicKey, $message)
    {
        return (new PublicKey($publicKey))->encrypt($message);
    }

    public function decryptMessage($privateKey, $message)
    {
        return (new PrivateKey($privateKey))->decrypt($message);
    }
}
