<?php

defined('ABSPATH') || exit;

class PlaneUpload {


    private static $apiKeyEncryptionEnabled = true;
    private static $encryptionMethod = 'aes-128-cbc';
//    private static $encryptionMethod = 'AES-256-CBC-HMAC-SHA256';

    public static $cartId;

    public static function getCartId() {
        if (null == static::$cartId) {
            static::$cartId = WC()->session->get("planeupload_cart_id");
            if (null == static::$cartId) {

                static::$cartId = sha1(json_encode(array(
                    rand(0, 10000), microtime(),
                    "c83486c4dba94a26b4649127d1c0111375edd2730c50b8c827a2e73295e727f8"
                )));
                WC()->session->set("planeupload_cart_id", static::$cartId);
            }
        }
        return static::$cartId;
    }

    public static function getItemSessionKey($cartItemKey) {
        return sha1($cartItemKey . static::getCartId());
    }

    public static function setCartButtonKeys($data) {
        $map = static::getCartButtonKeys();

        foreach ($data as $cartItemKey => $buttonKey) {
            $key = static::getItemSessionKey($cartItemKey);
            $map[$key] = $buttonKey;
        }

        WC()->session->set("planeupload-cart", json_encode($map));
        WC()->session->save_data();
    }

    public static function getCartButtonKeys() {
        $map = WC()->session->get("planeupload-cart");
        if (null == $map) {
            $map = [];
        } else {
            $map = json_decode($map);
        }
        if (is_object($map)) {
            $map = get_object_vars($map);
        }
        return $map;
    }


    private static function getEncryptionKeys() {
        $dbKey = get_option("planeupload_enc_key");
        if (null == $dbKey) {
            add_option("planeupload_enc_key", hash("sha256", json_encode([microtime(), rand(0, 999999), "4f8a6d0e86d4c8aeb5129de83db85b64fe6b74c37b8934cb02fed9acbb290ef6"])));
        }
        $dbKey = get_option("planeupload_enc_key");

        $pass = hash('sha256', json_encode([
            AUTH_SALT, "1fec252c80f60519b4d877caf58e2982e2631efc68e7f281091fff56d78918fe", $dbKey
        ]));
        $iv = substr(hash("sha256", $pass), 10, 16);
        return [$pass, $iv];

    }

    public static function isEncryptionEnabled() {
        return static::$apiKeyEncryptionEnabled
            && function_exists('openssl_get_cipher_methods')
            && in_array(static::$encryptionMethod,openssl_get_cipher_methods());
    }

    private static function encrypt($data) {
        if (preg_match('/^\$ENC_/', $data)) {
            return null;
        }
        list($pass, $iv) = static::getEncryptionKeys();
        return '$ENC_' . openssl_encrypt($data, static::$encryptionMethod, $pass, 0, $iv);
    }

    private static function decrypt($data) {
        if (!preg_match('/^\$ENC_/', $data)) {
            return null;
        }
        $data = preg_replace('/^\$ENC_/', "", $data);
        list($pass, $iv) = static::getEncryptionKeys();
        return openssl_decrypt($data, static::$encryptionMethod, $pass, 0, $iv);
    }

    public static function isApiKeySet() {
        $options = get_option('planeupload_options');
        $apiKEyEncrypted = null != $options && isset($options["planeupload_api_key"]) ? $options["planeupload_api_key"] : null;
        return null != $apiKEyEncrypted && strlen($apiKEyEncrypted) > 0;

    }

    private static function getApiKey() {

        $options = get_option('planeupload_options');
        $apiKey = null != $options && isset($options["planeupload_api_key"]) ? $options["planeupload_api_key"] : null;

        if (null == $apiKey || strlen($apiKey) == 0) {
            return "";
        }
        if (!static::isEncryptionEnabled()) {
            return $apiKey;
        }
        $saveCheck = null;
        if (!preg_match('/^\$ENC_/', $apiKey)) {
            $saveCheck = $apiKey;
            $apiKey = static::encrypt($apiKey);

        }
        $decrypted = static::decrypt($apiKey);
        if (null != $saveCheck) {
            if ($decrypted !== $saveCheck) {
                throw new Exception("decryption failed");
            }
            $options["planeupload_api_key"] = $apiKey;
            update_option("planeupload_options", $options);
        }
        return $decrypted;

    }

    private static function getPlaneUploadApiKeyHash() {

        return sha1("b91ab0cdb82cd1c9181f21088d9c4bf18ba063e562d2a3559052f86d2ef6608c"
            . static::getApiKey());

    }


    public static function planeuploadRequest($method, $params) {
        if (!in_array($method, ["provideButton", "getFileProviders", "getButtons", "saveButton", "confirmAttachment"])) {
            throw new Exception("illegal method");
        }

        $apiKey = static::getApiKey();
        if (null == $apiKey) {
            return null;
        }

        $response = wp_remote_post( "https://api.planeupload.com/api/" . $method, array(
                'method'      => 'POST',
                'timeout'     => 120,
                'blocking'    => true,
                'headers'     => ["Accept"=>"application/json",
                    "Content-Type"=>"application/json",
                    "apiKey" => $apiKey
                ],
                'body'        => null == $params ? "{}" : json_encode($params),
            )
        );
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }
        return json_decode($response["body"]);
    }


    public static function planeuploadGetPrototype() {
        $option = get_option('planeupload_prototype');
        $prototype = null;
        $hash = static::getPlaneUploadApiKeyHash();
        if (null == $hash) {
            return null;
        }
        if (null != $option) {
            $prototype = json_decode($option);

        }

        if (null == $prototype || $hash !== $prototype->hash) {
            $directory = "woocommerce-prototype";
            $systemTag = "woocommerce-prototype";
            $result = static::planeuploadRequest("provideButton", [
                "directory" => $directory,
                "systemTag" => $systemTag,
                "tag" => "WooCommerce prototype, please don't remove"
            ]);
            if (null == $result) {
                return null;
            }
            $prototype = [
                "hash" => $hash,
                "id" => $result->id,
                "attachmentKey" => $result->attachmentKey
            ];
            if (null != $option) {
                update_option("planeupload_prototype", json_encode($prototype));
            } else {
                add_option("planeupload_prototype", json_encode($prototype));
            }
        }

        if (is_array($prototype)) {
            $prototype = json_decode(json_encode($prototype));
        }
        return $prototype;
    }

    public static function getButtonOptions($scope=null) {
        $language = preg_replace("/-(.*)$/","",get_bloginfo("language"));
        $options = [
//            "label" => __("Upload"),
//            "dropFilesHere"=>__("Drop files here"),
            "userStatusRequireMessageTitle" => __("Message"),
            "language"=>$language
        ];
        if (isSet($scope)) {
            $options["scope"] = $scope;
        }
        return $options;
    }
    public static function checkRequirements() {
        if (!function_exists('openssl_get_cipher_methods')) {
            return "Your system seam's not to have openssl configured. It is required to encrypt the API key. 
            Please check out the readme.txt file.";
        }
        if (!in_array(static::$encryptionMethod, openssl_get_cipher_methods())) {
            return "Your system does not have " . static::$encryptionMethod . " encryption method available.
            Please check out the readme.txt file.";
        }
    }

}
