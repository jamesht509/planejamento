<?php
class PHPGangsta_GoogleAuthenticator {
    protected $_codeLength = 6;

    public function createSecret($secretLength = 16) {
        $validChars = $this->_getBase32LookupTable();
        unset($validChars[32]);
        $secret = '';
        for ($i = 0; $i < $secretLength; $i++) {
            $secret .= $validChars[array_rand($validChars)];
        }
        return $secret;
    }

    public function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }

        $secretkey = $this->_base32Decode($secret);
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        $hm = hash_hmac('SHA1', $time, $secretkey, true);
        $offset = ord(substr($hm, -1)) & 0x0F;
        $hashpart = substr($hm, $offset, 4);
        $value = unpack("N", $hashpart)[1];
        $value = $value & 0x7FFFFFFF;
        $modulo = pow(10, $this->_codeLength);
        return str_pad($value % $modulo, $this->_codeLength, '0', STR_PAD_LEFT);
    }

    public function verifyCode($secret, $code, $discrepancy = 1, $currentTimeSlice = null) {
        if ($currentTimeSlice === null) {
            $currentTimeSlice = floor(time() / 30);
        }

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if ($calculatedCode === $code) {
                return true;
            }
        }

        return false;
    }

    public function getQRCodeGoogleUrl($name, $secret, $title = null, $params = []) {
        $urlencoded = urlencode("otpauth://totp/{$name}?secret={$secret}");
        if ($title) {
            $params['issuer'] = $title;
        }
        foreach ($params as $key => $value) {
            $urlencoded .= '&'.urlencode($key).'='.urlencode($value);
        }
        return "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl={$urlencoded}";
    }

    protected function _base32Decode($secret) {
        if (empty($secret)) return '';
        $base32chars = $this->_getBase32LookupTable();
        $base32charsFlipped = array_flip($base32chars);
        $paddingCharCount = substr_count($secret, '=');
        $allowedValues = [6, 4, 3, 1, 0];
        if (!in_array($paddingCharCount, $allowedValues)) return false;
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -$allowedValues[$i]) != str_repeat('=', $allowedValues[$i])) {
                return false;
            }
        }

        $secret = str_replace('=', '', $secret);
        $binaryString = '';
        for ($i = 0; $i < strlen($secret); $i++) {
            $char = $secret[$i];
            if (!isset($base32charsFlipped[$char])) {
                return false;
            }
            $binaryString .= str_pad(decbin($base32charsFlipped[$char]), 5, '0', STR_PAD_LEFT);
        }

        $eightBits = str_split($binaryString, 8);
        $decoded = '';
        foreach ($eightBits as $byte) {
            if (strlen($byte) === 8) {
                $decoded .= chr(bindec($byte));
            }
        }
        return $decoded;
    }

    protected function _getBase32LookupTable() {
        return [
            'A', 'B', 'C', ' 'D', 'E', 'F', 'G', 'H',
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
            'Y', 'Z', '2', '3', '4', '5', '6', '7', '='
        ];
    }
}
?>