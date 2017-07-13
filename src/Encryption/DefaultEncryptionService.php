<?php

namespace CustomerManagementFrameworkBundle\Encryption;

use CustomerManagementFrameworkBundle\Config;
use CustomerManagementFrameworkBundle\Plugin;
use CustomerManagementFrameworkBundle\Traits\LoggerAware;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class DefaultEncryptionService implements EncryptionServiceInterface
{
    use LoggerAware;

    /**
     * @var Key
     */
    protected $defaultKey;

    /**
     * Get the default key used for encryption/decryption if no key is passed
     *
     * @return Key
     */
    public function getDefaultKey()
    {
        if (null === $this->defaultKey) {
            $secret = Config::getConfig()->Encryption->secret;
            if (!$secret || empty($secret)) {
                throw new \RuntimeException('Need an encryption secret');
            }

            $this->defaultKey = Key::loadFromAsciiSafeString($secret);
        }

        return $this->defaultKey;
    }

    /**
     * Encrypt data with key (will fall back to default key if none given)
     *
     * @param string $plaintext
     * @param Key|null $key
     * @param bool $rawBinary
     * @return string
     */
    public function encrypt($plaintext, Key $key = null, $rawBinary = false)
    {
        if (empty($plaintext)) {
            $this->getLogger()->warning('Returning empty encrypt() result as plaintext was empty');

            return '';
        }

        if (!$key) {
            $key = $this->getDefaultKey();
        }

        return Crypto::encrypt($plaintext, $key, $rawBinary);
    }

    /**
     * Decrypt ciphertext with key (will fall back to default key if none given)
     *
     * @param string $ciphertext
     * @param Key|null $key
     * @param bool $rawBinary
     * @return string
     */
    public function decrypt($ciphertext, Key $key = null, $rawBinary = false)
    {
        if (empty($ciphertext)) {
            $this->getLogger()->warning('Returning empty decrypt() result as ciphertext was empty');

            return '';
        }

        if (!$key) {
            $key = $this->getDefaultKey();
        }

        return Crypto::decrypt($ciphertext, $key, $rawBinary);
    }

    /**
     * @return array|null
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.sleep
     */
    public function __sleep()
    {
        // do not serialize default key
        $this->defaultKey = null;
    }
}
