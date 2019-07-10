<?php

namespace Drupal\app\AppBundle\Utils;


class PaymentFiscalSecond
{
    private $baseUrl = "https://ws.roboxchange.com/RoboFiscal/Receipt/Attach";

    /**
     * Class constructor.
     *
     * @param $orderId
     * @param $tax
     * @param $paymentMethod
     * @param $paymentObject
     * @param $sno
     * @internal param string $login login of Merchant
     * @internal param string $paymentPassword password #1
     * @internal param string $validationPassword password #2
     * @internal param bool $testMode use test server
     */
    public function __construct($orderId, $tax, $paymentMethod, $paymentObject, $sno)
    {
        $this->orderId = $orderId;
        $this->order = commerce_order_load($this->orderId);
        $this->tax = $tax;
        $this->paymentMethod = $paymentMethod;
        $this->paymentObject = $paymentObject;
        $this->sno = $sno;
    }

    private function getSettings()
    {
        $settings = commerce_payment_method_instance_load('commerce_robokassa|commerce_payment_commerce_robokassa');
        return $settings['settings'];
    }

    private function getItems()
    {

        $order = $this->order;
        $items = [];
        foreach ($order->commerce_line_items['und'] as $line) {
            $line_item = commerce_line_item_load($line['line_item_id']);
            $items[] = [
                'name' => $line_item->line_item_label,
                'quantity' => floatval($line_item->quantity),
                'sum' => floatval(round($line_item->commerce_total['und'][0]['amount'] / 100, 2)),
                'tax' => $this->tax,
                'payment_method' => $this->paymentMethod,
                'payment_object' => $this->paymentObject
            ];
        }

        return $items;

    }

    public function collectData()
    {
        global $base_url;
        $order = $this->order;

        $settings = $this->getSettings();
        $postdata = array(
            "merchantId" => $settings['MrchLogin'],
            "id" => $this->orderId + 100000,
            "originId" => $this->orderId,
            "operation" => "sell",
            "sno" => $this->sno,
            "url" => $base_url,
            "total" => $order->commerce_order_total['und'][0]['amount'] / 100,
            "items" => $this->getItems(),
            "client" => [
                "email" => $order->mail,
            ],
            "payments" => [
                [
                    "type" => "2",
                    "sum" => $order->commerce_order_total['und'][0]['amount'] / 100
                ]
            ],
            "vats" => [
                [
                    "type" => "none",
                    "sum" => "0"
                ]
            ]
        );


        /**
         * json encode
         */
        $post = json_encode($postdata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

        /**
         * replace symbols
         */
        $post1 = str_replace("+", "-", $post);
        $post2 = str_replace("/", "_", $post1);

        /**
         * b64
         */
        $b64 = base64_encode($post2);

        /**
         * remove =
         */
        $start = mb_substr($b64, 0, -2);
        $end = mb_substr($b64, -2);
        $clearB64 = $start . str_replace("=", "", $end);

        /**
         * add pass
         */
        $clearB64WithPass = $clearB64 . $settings['pass1'];

        /**
         * md5
         */
        $md5 = md5($clearB64WithPass);

        /**
         * encode in b64
         */
        $newB64 = base64_encode($md5);

        /**
         * remove =
         */
        $newStart = mb_substr($newB64, 0, -2);
        $newEnd = mb_substr($newB64, -2);
        $clearNewB64 = $newStart . str_replace("=", "", $newEnd);

        $result = $clearB64 . "." . $clearNewB64;

        $ch = curl_init($this->baseUrl);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $result);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array("application/json; charset=UTF-8", "Content-Length: " . strlen($result)));
        $res = curl_exec($ch);
        curl_close($ch);

        $this->validateResult($res);

    }

    private function validateResult($res) {
        // watchdog if something went wrong
        $result = json_decode($res, true);
        if (!$this->resultIsOk($result)) {
            $description = (isset($result['ResultDescription']) ? 'Error in the second Robokassa fiscal receipt' . ' ' . $result['ResultDescription'] : 'Error in the second Robokassa fiscal receipt');
            watchdog('commerce_robokassa', $description . ' order id: "!orderid"', array('!orderid' => $this->orderId), WATCHDOG_WARNING);
        }

    }

    private function resultIsOk($result) {
        if (isset($result['ResultCode']) && $result['ResultCode'] == 0 && isset($result['ResultDescription']) && $result['ResultDescription'] == 'ok') {
            return true;
        }
        else {
            return false;
        }
    }

}