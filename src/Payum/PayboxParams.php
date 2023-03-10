<?php

declare(strict_types=1);

namespace Klehm\SyliusPayumCA3xcbPlugin\Payum;

use Sylius\Component\Core\Model\AddressInterface;
use Sylius\Component\Core\Model\CustomerInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Locale\Context\LocaleContextInterface;

class PayboxParams
{
    // Default servers urls
    const SERVER_TEST = "https://preprod-tpeweb.paybox.com/php/";
    const SERVER_PRODUCTION = "https://tpeweb.paybox.com/php/";

    const INTERFACE_VERSION = "IR_WS_2.17";
    const INSTALMENT = "INSTALMENT";

    // Requests params values
    const PBX_RETOUR_VALUE = 'Mt:M;Ref:R;Auto:A;Appel:T;Abo:B;Reponse:E;Transaction:S;Pays:Y;Signature:K';
    const PBX_DEVISE_EURO = '978';

    // Requests params keys
    const PBX_SITE = 'PBX_SITE';
    const PBX_RANG = 'PBX_RANG';
    const PBX_IDENTIFIANT = 'PBX_IDENTIFIANT';
    const PBX_HASH = 'PBX_HASH';
    const PBX_RETOUR = 'PBX_RETOUR';
    const PBX_HMAC = 'PBX_HMAC';
    const PBX_TYPEPAIEMENT = 'PBX_TYPEPAIEMENT';
    const PBX_TYPECARTE = 'PBX_TYPECARTE';
    const PBX_TOTAL = 'PBX_TOTAL';
    const PBX_DEVISE = 'PBX_DEVISE';
    const PBX_CMD = 'PBX_CMD';
    const PBX_PORTEUR = 'PBX_PORTEUR';
    const PBX_EFFECTUE = 'PBX_EFFECTUE';
    const PBX_ATTENTE = 'PBX_ATTENTE';
    const PBX_ANNULE = 'PBX_ANNULE';
    const PBX_REFUSE = 'PBX_REFUSE';
    const PBX_REPONDRE_A = 'PBX_REPONDRE_A';
    const PBX_TIME = 'PBX_TIME';
    const PBX_SOURCE = 'PBX_SOURCE';
    const PBX_BILLING = "PBX_BILLING";
    const PBX_CUSTOMER = "PBX_CUSTOMER";
    const PBX_SHOPPINGCART = "PBX_SHOPPINGCART";
    const PBX_ERRORCODETEST = "PBX_ERRORCODETEST";

    private array $currencies = [
        'EUR' => '978', 'USD' => '840', 'CHF' => '756', 'GBP' => '826',
        'CAD' => '124', 'JPY' => '392', 'MXP' => '484', 'TRY' => '949',
        'AUD' => '036', 'NZD' => '554', 'NOK' => '578', 'BRC' => '986',
        'ARP' => '032', 'KHR' => '116', 'TWD' => '901', 'SEK' => '752',
        'DKK' => '208', 'KRW' => '410', 'SGD' => '702', 'XPF' => '953',
        'XOF' => '952'
    ];

    private LocaleContextInterface $localeContext;

    public function __construct(LocaleContextInterface $localeContext)
    {
        $this->localeContext = $localeContext;
    }

    public function convertCurrencyToCurrencyCode($currency)
    {
        if (!\in_array($currency, array_keys($this->currencies))) {
            throw new \InvalidArgumentException("Unknown currencyCode $currency.");
        }
        return $this->currencies[$currency];
    }

    public function setBilling(OrderInterface $order)
    {
        /** @var AddressInterface $billingAddress */
        $billingAddress = $order->getBillingAddress();
        $firstName = $this->formatTextValue($billingAddress->getFirstName(), 'ANP', 30);
        $lastName = $this->formatTextValue($billingAddress->getLastName(), 'ANP', 30);
        $addressLine1 = $this->formatTextValue($billingAddress->getFullName(), 'ANS', 50);
        //$addressLine2 = $this->formatTextValue('', 'ANS', 50);
        $zipCode = $this->formatTextValue($billingAddress->getPostcode(), 'ANS', 16);
        $city = $this->formatTextValue($billingAddress->getCity(), 'ANS', 50);
        $countryCode = $billingAddress->getCountryCode() ? $billingAddress->getCountryCode() : 'FR';
        $dataIso = (new \League\ISO3166\ISO3166)->alpha2($countryCode);
        //default french if not found
        $countryIso3661 = $dataIso['numeric'] ?? 250;

        $xml = sprintf(
            '<?xml version="1.0" encoding="utf-8"?><Billing><Address><FirstName>%s</FirstName><LastName>%s</LastName><Address1>%s</Address1><ZipCode>%s</ZipCode><City>%s</City><CountryCode>%d</CountryCode></Address></Billing>',
            $firstName,
            $lastName,
            $addressLine1,
            $zipCode,
            $city,
            $countryIso3661
        );

        return $xml;
    }
    
    public function setCustomer($customer)
    {
        $xml = sprintf(
            '<?xml version="1.0" encoding="utf-8"?><Customer><Id>%s</Id></Customer>',
            $customer->getId()
        );

        return $xml;
    }

    public function setShoppingCart($value)
    {
        // totalQuantity must be less or equal than 99
        $totalQuantity = min($value, 99);
        $xml = sprintf('<?xml version="1.0" encoding="utf-8"?><shoppingcart><total><totalQuantity>%d</totalQuantity></total></shoppingcart>', $totalQuantity);

        return $xml;
    }

    /**
     * Format a value to respect specific rules
     *
     * @param string $value
     * @param string $type
     * @param int $maxLength
     * @return string
     */
    private function formatTextValue($value, $type, $maxLength = null)
    {
        /*
        AN : Alphanumerical without special characters
        ANP : Alphanumerical with spaces and special characters
        ANS : Alphanumerical with special characters
        N : Numerical only
        A : Alphabetic only
        */

        switch ($type) {
            default:
            case 'AN':
                $value = $this->removeAccents($value);
                break;
            case 'ANP':
                $value = $this->removeAccents($value);
                $value = preg_replace('/[^-. a-zA-Z0-9]/', '', $value);
                break;
            case 'ANS':
                break;
            case 'N':
                $value = preg_replace('/[^0-9.]/', '', $value);
                break;
            case 'A':
                $value = $this->removeAccents($value);
                $value = preg_replace('/[^A-Za-z]/', '', $value);
                break;
        }
        // Remove carriage return characters
        $value = trim(preg_replace("/\r|\n/", '', $value));
        // Cut the string when needed
        if (!empty($maxLength) && is_numeric($maxLength) && $maxLength > 0) {
            if (function_exists('mb_strlen')) {
                if (mb_strlen($value) > $maxLength) {
                    $value = mb_substr($value, 0, $maxLength);
                }
            } elseif (strlen($value) > $maxLength) {
                $value = substr($value, 0, $maxLength);
            }
        }

        return $value;
    }

    public function removeAccents($string)
    {
        if (!preg_match('/[\x80-\xff]/', $string)) {
            return $string;
        }
        if ($this->seemsUtf8($string)) {
            $chars = [
                // Decompositions for Latin-1 Supplement.
                '??' => 'a',
                '??' => 'o',
                '??' => 'A',
                '??' => 'A',
                '??' => 'A',
                '??' => 'A',
                '??' => 'A',
                '??' => 'A',
                '??' => 'AE',
                '??' => 'C',
                '??' => 'E',
                '??' => 'E',
                '??' => 'E',
                '??' => 'E',
                '??' => 'I',
                '??' => 'I',
                '??' => 'I',
                '??' => 'I',
                '??' => 'D',
                '??' => 'N',
                '??' => 'O',
                '??' => 'O',
                '??' => 'O',
                '??' => 'O',
                '??' => 'O',
                '??' => 'U',
                '??' => 'U',
                '??' => 'U',
                '??' => 'U',
                '??' => 'Y',
                '??' => 'TH',
                '??' => 's',
                '??' => 'a',
                '??' => 'a',
                '??' => 'a',
                '??' => 'a',
                '??' => 'a',
                '??' => 'a',
                '??' => 'ae',
                '??' => 'c',
                '??' => 'e',
                '??' => 'e',
                '??' => 'e',
                '??' => 'e',
                '??' => 'i',
                '??' => 'i',
                '??' => 'i',
                '??' => 'i',
                '??' => 'd',
                '??' => 'n',
                '??' => 'o',
                '??' => 'o',
                '??' => 'o',
                '??' => 'o',
                '??' => 'o',
                '??' => 'o',
                '??' => 'u',
                '??' => 'u',
                '??' => 'u',
                '??' => 'u',
                '??' => 'y',
                '??' => 'th',
                '??' => 'y',
                '??' => 'O',
                // Decompositions for Latin Extended-A.
                '??' => 'A',
                '??' => 'a',
                '??' => 'A',
                '??' => 'a',
                '??' => 'A',
                '??' => 'a',
                '??' => 'C',
                '??' => 'c',
                '??' => 'C',
                '??' => 'c',
                '??' => 'C',
                '??' => 'c',
                '??' => 'C',
                '??' => 'c',
                '??' => 'D',
                '??' => 'd',
                '??' => 'D',
                '??' => 'd',
                '??' => 'E',
                '??' => 'e',
                '??' => 'E',
                '??' => 'e',
                '??' => 'E',
                '??' => 'e',
                '??' => 'E',
                '??' => 'e',
                '??' => 'E',
                '??' => 'e',
                '??' => 'G',
                '??' => 'g',
                '??' => 'G',
                '??' => 'g',
                '??' => 'G',
                '??' => 'g',
                '??' => 'G',
                '??' => 'g',
                '??' => 'H',
                '??' => 'h',
                '??' => 'H',
                '??' => 'h',
                '??' => 'I',
                '??' => 'i',
                '??' => 'I',
                '??' => 'i',
                '??' => 'I',
                '??' => 'i',
                '??' => 'I',
                '??' => 'i',
                '??' => 'I',
                '??' => 'i',
                '??' => 'IJ',
                '??' => 'ij',
                '??' => 'J',
                '??' => 'j',
                '??' => 'K',
                '??' => 'k',
                '??' => 'k',
                '??' => 'L',
                '??' => 'l',
                '??' => 'L',
                '??' => 'l',
                '??' => 'L',
                '??' => 'l',
                '??' => 'L',
                '??' => 'l',
                '??' => 'L',
                '??' => 'l',
                '??' => 'N',
                '??' => 'n',
                '??' => 'N',
                '??' => 'n',
                '??' => 'N',
                '??' => 'n',
                '??' => 'n',
                '??' => 'N',
                '??' => 'n',
                '??' => 'O',
                '??' => 'o',
                '??' => 'O',
                '??' => 'o',
                '??' => 'O',
                '??' => 'o',
                '??' => 'OE',
                '??' => 'oe',
                '??' => 'R',
                '??' => 'r',
                '??' => 'R',
                '??' => 'r',
                '??' => 'R',
                '??' => 'r',
                '??' => 'S',
                '??' => 's',
                '??' => 'S',
                '??' => 's',
                '??' => 'S',
                '??' => 's',
                '??' => 'S',
                '??' => 's',
                '??' => 'T',
                '??' => 't',
                '??' => 'T',
                '??' => 't',
                '??' => 'T',
                '??' => 't',
                '??' => 'U',
                '??' => 'u',
                '??' => 'U',
                '??' => 'u',
                '??' => 'U',
                '??' => 'u',
                '??' => 'U',
                '??' => 'u',
                '??' => 'U',
                '??' => 'u',
                '??' => 'U',
                '??' => 'u',
                '??' => 'W',
                '??' => 'w',
                '??' => 'Y',
                '??' => 'y',
                '??' => 'Y',
                '??' => 'Z',
                '??' => 'z',
                '??' => 'Z',
                '??' => 'z',
                '??' => 'Z',
                '??' => 'z',
                '??' => 's',
                // Decompositions for Latin Extended-B.
                '??' => 'S',
                '??' => 's',
                '??' => 'T',
                '??' => 't',
                // Euro sign.
                '???' => 'E',
                // GBP (Pound) sign.
                '??' => '',
                // Vowels with diacritic (Vietnamese).
                // Unmarked.
                '??' => 'O',
                '??' => 'o',
                '??' => 'U',
                '??' => 'u',
                // Grave accent.
                '???' => 'A',
                '???' => 'a',
                '???' => 'A',
                '???' => 'a',
                '???' => 'E',
                '???' => 'e',
                '???' => 'O',
                '???' => 'o',
                '???' => 'O',
                '???' => 'o',
                '???' => 'U',
                '???' => 'u',
                '???' => 'Y',
                '???' => 'y',
                // Hook.
                '???' => 'A',
                '???' => 'a',
                '???' => 'A',
                '???' => 'a',
                '???' => 'A',
                '???' => 'a',
                '???' => 'E',
                '???' => 'e',
                '???' => 'E',
                '???' => 'e',
                '???' => 'I',
                '???' => 'i',
                '???' => 'O',
                '???' => 'o',
                '???' => 'O',
                '???' => 'o',
                '???' => 'O',
                '???' => 'o',
                '???' => 'U',
                '???' => 'u',
                '???' => 'U',
                '???' => 'u',
                '???' => 'Y',
                '???' => 'y',
                // Tilde.
                '???' => 'A',
                '???' => 'a',
                '???' => 'A',
                '???' => 'a',
                '???' => 'E',
                '???' => 'e',
                '???' => 'E',
                '???' => 'e',
                '???' => 'O',
                '???' => 'o',
                '???' => 'O',
                '???' => 'o',
                '???' => 'U',
                '???' => 'u',
                '???' => 'Y',
                '???' => 'y',
                // Acute accent.
                '???' => 'A',
                '???' => 'a',
                '???' => 'A',
                '???' => 'a',
                '???' => 'E',
                '???' => 'e',
                '???' => 'O',
                '???' => 'o',
                '???' => 'O',
                '???' => 'o',
                '???' => 'U',
                '???' => 'u',
                // Dot below.
                '???' => 'A',
                '???' => 'a',
                '???' => 'A',
                '???' => 'a',
                '???' => 'A',
                '???' => 'a',
                '???' => 'E',
                '???' => 'e',
                '???' => 'E',
                '???' => 'e',
                '???' => 'I',
                '???' => 'i',
                '???' => 'O',
                '???' => 'o',
                '???' => 'O',
                '???' => 'o',
                '???' => 'O',
                '???' => 'o',
                '???' => 'U',
                '???' => 'u',
                '???' => 'U',
                '???' => 'u',
                '???' => 'Y',
                '???' => 'y',
                // Vowels with diacritic (Chinese, Hanyu Pinyin).
                '??' => 'a',
                // Macron.
                '??' => 'U',
                '??' => 'u',
                // Acute accent.
                '??' => 'U',
                '??' => 'u',
                // Caron.
                '??' => 'A',
                '??' => 'a',
                '??' => 'I',
                '??' => 'i',
                '??' => 'O',
                '??' => 'o',
                '??' => 'U',
                '??' => 'u',
                '??' => 'U',
                '??' => 'u',
                // Grave accent.
                '??' => 'U',
                '??' => 'u',
            ];
            // Used for locale-specific rules.
            $locale = $this->localeContext->getLocaleCode();
            if (in_array($locale, ['de_DE', 'de_DE_formal', 'de_CH', 'de_CH_informal', 'de_AT'], true)) {
                $chars['??'] = 'Ae';
                $chars['??'] = 'ae';
                $chars['??'] = 'Oe';
                $chars['??'] = 'oe';
                $chars['??'] = 'Ue';
                $chars['??'] = 'ue';
                $chars['??'] = 'ss';
            } elseif ('da_DK' === $locale) {
                $chars['??'] = 'Ae';
                $chars['??'] = 'ae';
                $chars['??'] = 'Oe';
                $chars['??'] = 'oe';
                $chars['??'] = 'Aa';
                $chars['??'] = 'aa';
            } elseif ('ca' === $locale) {
                $chars['l??l'] = 'll';
            } elseif ('sr_RS' === $locale || 'bs_BA' === $locale) {
                $chars['??'] = 'DJ';
                $chars['??'] = 'dj';
            }
            $string = strtr($string, $chars);
        } else {
            $chars = array();
            // Assume ISO-8859-1 if not UTF-8.
            $chars['in'] = "\x80\x83\x8a\x8e\x9a\x9e"
                . "\x9f\xa2\xa5\xb5\xc0\xc1\xc2"
                . "\xc3\xc4\xc5\xc7\xc8\xc9\xca"
                . "\xcb\xcc\xcd\xce\xcf\xd1\xd2"
                . "\xd3\xd4\xd5\xd6\xd8\xd9\xda"
                . "\xdb\xdc\xdd\xe0\xe1\xe2\xe3"
                . "\xe4\xe5\xe7\xe8\xe9\xea\xeb"
                . "\xec\xed\xee\xef\xf1\xf2\xf3"
                . "\xf4\xf5\xf6\xf8\xf9\xfa\xfb"
                . "\xfc\xfd\xff";

            $chars['out'] = 'EfSZszYcYuAAAAAACEEEEIIIINOOOOOOUUUUYaaaaaaceeeeiiiinoooooouuuuyy';
            $string = strtr($string, $chars['in'], $chars['out']);
            $double_chars = [];
            $double_chars['in'] = ["\x8c", "\x9c", "\xc6", "\xd0", "\xde", "\xdf", "\xe6", "\xf0", "\xfe"];
            $double_chars['out'] = ['OE', 'oe', 'AE', 'DH', 'TH', 'ss', 'ae', 'dh', 'th'];
            $string = str_replace($double_chars['in'], $double_chars['out'], $string);
        }

        return $string;
    }

    private function seemsUtf8($str)
    {
        $this->mbstringBinarySafeEncoding();
        $length = strlen($str);
        $this->mbstringBinarySafeEncoding(true);
        for ($i = 0; $i < $length; $i++) {
            $c = ord($str[$i]);
            if ($c < 0x80) {
                $n = 0; // 0bbbbbbb
            } elseif (($c & 0xE0) == 0xC0) {
                $n = 1; // 110bbbbb
            } elseif (($c & 0xF0) == 0xE0) {
                $n = 2; // 1110bbbb
            } elseif (($c & 0xF8) == 0xF0) {
                $n = 3; // 11110bbb
            } elseif (($c & 0xFC) == 0xF8) {
                $n = 4; // 111110bb
            } elseif (($c & 0xFE) == 0xFC) {
                $n = 5; // 1111110b
            } else {
                return false; // Does not match any model.
            }
            for ($j = 0; $j < $n; $j++) { // n bytes matching 10bbbbbb follow ?
                if ((++$i == $length) || ((ord($str[$i]) & 0xC0) != 0x80)) {
                    return false;
                }
            }
        }
        return true;
    }

    private function mbstringBinarySafeEncoding(bool $reset = false)
    {
        static $encodings = array();
        static $overloaded = null;

        if (is_null($overloaded)) {
            $overloaded = function_exists('mb_internal_encoding') && (ini_get('mbstring.func_overload') & 2); // phpcs:ignore PHPCompatibility.IniDirectives.RemovedIniDirectives.mbstring_func_overloadDeprecated
        }

        if (false === $overloaded) {
            return;
        }

        if (!$reset) {
            $encoding = mb_internal_encoding();
            array_push($encodings, $encoding);
            mb_internal_encoding('ISO-8859-1');
        }

        if ($reset && $encodings) {
            $encoding = array_pop($encodings);
            mb_internal_encoding($encoding);
        }
    }
}
