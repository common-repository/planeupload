<?php
defined('ABSPATH') || exit;

/**
 * Plugin Name: PlaneUpload
 * Plugin URI: https://planeupload.com/woocommerce.html
 * Description: Let your customers upload files to your WooCommerce orders
 * Version: 1.0.4
 * Author: yagular
 * Author URI: https://yagular.com
 */

include_once "planeupload-settings.php";
include_once "planeupload-client.php";

add_action('woocommerce_order_item_meta_end', 'planeupload_order_item_customer');
add_action('woocommerce_order_item_add_action_buttons', 'planeupload_order_admin');
add_action('woocommerce_after_cart_item_name', 'planeupload_cart_item');

add_action('woocommerce_checkout_create_order_line_item', 'planeupload_pre_order_item', 10, 4);
add_action('woocommerce_checkout_order_created', 'planeupload_on_order',10);

add_filter('woocommerce_hidden_order_itemmeta', 'plane_upload_custom_woocommerce_hidden_order_itemmeta', 10, 1);

add_filter('woocommerce_after_add_to_cart_button', 'planeupload_product');
add_filter('woocommerce_add_to_cart', 'planeupload_on_add_to_cart',10,6);

add_filter('woocommerce_init', 'planeupload_woocommerce_init',100);




function plane_upload_custom_woocommerce_hidden_order_itemmeta($arr) {
    $arr[] = '_planeupload';
    return $arr;
}

function planeupload_on_order($order) {

    foreach($order->get_items() as $item) {

        /* @var $item WC_Order_Item */
        $planeKey = $item->get_meta("_planeupload_key");

        if (null == $planeKey) {
            continue;
        }

        $directory = "orders/" . $order->get_id() . "/" . $item->get_id() . "-" . $item->get_name();
        $tag = $order->get_billing_email() . " - " . $order->get_id() . " - " . $item->get_id() . " " . $item->get_name();

        $button = PlaneUpload::planeuploadRequest("confirmAttachment",[
            "key"=>$planeKey,
            "directory"=>$directory,
            "tag"=>$tag,
            "ignoreIfNoFiles"=>false
        ]);

        if (null != $button) {
            $item->update_meta_data("_planeupload", json_encode(
                ["id" => $button->id, "web" => $button->webKey, "admin" => $button->adminKey]
            ));
            $item->delete_meta_data("_planeupload_key");
            $item->save_meta_data();
        }

    }

    if (null != WC()->session->get("planeupload-cart")) {
        WC()->session->set("planeupload-cart",null);
        WC()->session->save_data();
    }
}


function planeupload_pre_order_item($orderItem, $cart_item_key, $values, $order) {

    /* @var $orderItem WC_Order_Item */
    $keys = PlaneUpload::getCartButtonKeys();
    if (isSet($keys[PlaneUpload::getItemSessionKey($cart_item_key)])) {
        $orderItem->update_meta_data("_planeupload_key",$keys[PlaneUpload::getItemSessionKey($cart_item_key)]);
    }

}


function planeupload_order_button($order, $orderItem) {
    /* @var $orderItem WC_Order_Item */
    $meta = $orderItem->get_meta("_planeupload");
    if (null != $meta) {
        $button = json_decode($meta);
        $button->key = $button->admin;
        return $button;
    }
    $systemTag = "wc-" . $order->get_id() . "-" . $orderItem->get_id();
    $directory = "orders/" . $order->get_id() . "/" . $orderItem->get_id() . "-" . $orderItem->get_name();
    $button = PlaneUpload::planeuploadRequest("provideButton", [
        "directory" => $directory,
        "buttonPrototypeId"=>PlaneUpload::planeuploadGetPrototype()->id,
        "systemTag" => $systemTag,
        "tag" => "WooCommerce " . $order->get_billing_email() . " - " . $order->get_id() . " - " . $orderItem->get_id() . " " . $orderItem->get_name()
    ]);
    $orderItem->update_meta_data("_planeupload", json_encode(
        ["id" => $button->id, "web" => $button->webKey, "admin" => $button->adminKey]
    ));
    return $button;

}

function planeupload_order_admin($order) {
    include "planeupload-order-admin.php";
}

function planeupload_order_item_customer($data) {
    $orderId = wc_get_order_id_by_order_item_id($data);
    $order = wc_get_order($orderId);
    foreach ($order->get_items() as $item) {
        if ($item->get_id() == (int)$data) {
            $button = planeupload_order_button($order, $item);
            if (null != $button) {
                $key = $button->web;
                include "planeupload-button.php";
            }
        }
    }
}

function planeupload_cart_button($productId,$cartItemId) {
    $prototype = PlaneUpload::planeuploadGetPrototype();
    $key = $prototype->attachmentKey;
    $scope = preg_replace("/^https?:\/\//","",get_home_url()."/product-".$productId);
    $map = PlaneUpload::getCartButtonKeys();
    $cartSet = isSet($map[PlaneUpload::getItemSessionKey($cartItemId)]);
    include "planeupload-button.php";
}

function planeupload_cart_item($item) {

    planeupload_cart_button($item["product_id"],$item["key"]);
}

function planeupload_product() {
    global $product;
    /* @var $product WC_Product_Simple */
    planeupload_cart_button($product->get_id(),null);
}


function planeupload_woocommerce_init() {
    if (isSet($_REQUEST["planeupload_cartset"])) {
        $data = json_decode(file_get_contents("php://input"));
        PlaneUpload::setCartButtonKeys($data->map);
        echo json_encode(PlaneUpload::getCartButtonKeys());
        exit;
    }
}

function planeupload_on_add_to_cart($cart_item_key, $product_id, $quantity, $variation_id, $variation, $cart_item_data) {
    if (isSet($_REQUEST["PLANE_UPLOAD_KEY"])) {
        PlaneUpload::setCartButtonKeys(["$cart_item_key"=>sanitize_text_field($_REQUEST["PLANE_UPLOAD_KEY"])]);
    }
}





