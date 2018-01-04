<?php

class ControllerExtensionPaymentBlueoceanpay extends Controller {

    public function index() {
        $data['button_confirm'] = $this->language->get('button_confirm');

        $data['redirect'] = $this->url->link('extension/payment/blueoceanpay/qrcode');

        return $this->load->view('extension/payment/blueoceanpay', $data);
    }

    public function qrcode() {
        $this->load->language('extension/payment/blueoceanpay');

        $this->document->setTitle($this->language->get('heading_title'));
        $this->document->addScript('catalog/view/javascript/qrcode.js');

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home')
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_checkout'),
            'href' => $this->url->link('checkout/checkout', '', true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_qrcode'),
            'href' => $this->url->link('extension/payment/blueoceanpay/qrcode')
        );

        $this->load->model('checkout/order');

        $order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);
        $order_id = trim($order_info['order_id']);

        // 获取商品信息
        // $order_products = $this->model_checkout_order->getOrderProducts($order_id);

        $data['order_id'] = $order_id;
        $subject = trim($this->config->get('config_name'));
        $currency = $this->config->get('payment_blueoceanpay_currency');
        $total_amount = trim($this->currency->format($order_info['total'], $currency, '', false));

        // 支付接口
        $url = 'http://api.hk.blueoceanpay.com/wechat/order/create';
        // 支付订单数据
        $requestData = [
            'appid'                => $this->config->get('payment_blueoceanpay_app_id'),
            'nonce_str'         => $this->getRandChar(10),
            'trade_type'        => 'NATIVE',
            'body'              => $subject, //　店铺名称 or 商品名称
            'out_trade_no'      => date('YmdHis', time()) . '-' . $order_id,
            'total_fee'         => $total_amount * 100,
            'spbill_create_ip'  => $order_info['ip'],
            'notify_url'        => $this->url->link('extension/payment/blueoceanpay/callback')
        ];
        $app_key =  $this->config->get('payment_blueoceanpay_app_secret');
        $requestData['sign'] = $this->sign($requestData, $app_key);

        $result = self::httpPost($url, json_encode($requestData));
        $returnData = json_decode($result, true);

        $data['error'] = '';
        $data['code_url'] = '';

        if($returnData['code'] != 200){
            if ($returnData['data'] != '') {
                $data['error_warning'] = $returnData['message'] . ': ' . $returnData['data'];
            } else {
                $data['error_warning'] = $returnData['message'];
            }
        } else {
            $data['code_url'] = $returnData['data']['code_url'];
        }

        $data['action_success'] = $this->url->link('checkout/success');

        $data['column_left'] = $this->load->controller('common/column_left');
        $data['column_right'] = $this->load->controller('common/column_right');
        $data['content_top'] = $this->load->controller('common/content_top');
        $data['content_bottom'] = $this->load->controller('common/content_bottom');
        $data['footer'] = $this->load->controller('common/footer');
        $data['header'] = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view('extension/payment/blueoceanpay_qrcode', $data));
    }

    public function isOrderPaid() {
        $json = array();

        $json['result'] = false;

        if (isset($this->request->get['order_id'])) {
            $order_id = $this->request->get['order_id'];

            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);

            if ($order_info['order_status_id'] == $this->config->get('payment_blueoceanpay_completed_status_id')) {
                $json['result'] = true;
            }
        }

        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }

    /**
     * 支付回调，更新订单状态。
     */
    public function callback() {
        $data = $_POST;
        if (($data['sign'] != $this->sign($data, $this->config->get('payment_blueoceanpay_app_secret')))) {
            $this->log->write('Sign Error');
            exit;
        }

        if ($data) {
            $order_id = explode('-', $data['out_trade_no'])[1];
            $this->load->model('checkout/order');
            $order_info = $this->model_checkout_order->getOrder($order_id);
            if ($order_info) {
                $order_status_id = $order_info["order_status_id"];
                if (!$order_status_id) {
                    $this->model_checkout_order->addOrderHistory($order_id, $this->config->get('payment_blueoceanpay_completed_status_id'));
                }
            }
            echo 'SUCCESS';
            exit;
        } else {
            $this->log->write('Blueocean Pay Error: ' . $data['message']);
        }
    }

    /**
     * 以post方式提交请求
     * @param string $url
     * @param array|string $data
     * @return bool|mixed
     */
    static public function httpPost($url, $data)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, true);
        if (is_array($data)) {
            foreach ($data as &$value) {
                if (is_string($value) && stripos($value, '@') === 0 && class_exists('CURLFile', false)) {
                    $value = new CURLFile(realpath(trim($value, '@')));
                }
            }
        }
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data) {
            return $data;
        }
        return false;
    }

    /**
     * 数组数据签名
     * @param array $data 参数
     * @param string $key 密钥
     * @return string 签名
     */
    public function sign($data, $key) {
        $ignoreKeys = ['sign', 'key'];
        ksort($data);
        $signString = '';
        foreach ($data as $k => $v) {
            if (in_array($k, $ignoreKeys)) {
                unset($data[$k]);
                continue;
            }
            $signString .= "{$k}={$v}&";
        }
        $signString .= "key={$key}";
        return strtoupper(md5($signString));
    }

    /**
     * 生成随机字符串
     * @param $length
     * @return null|string
     */
    public function getRandChar($length) {
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol) - 1;

        for ($i = 0; $i < $length; $i++) {
            $str .= $strPol[rand(0, $max)];
        }
        return $str;
    }

    /**
     *
     * 通过 slack 查看临时调试信息
     *
     * @param mix $data
     * @return none
     */
    function slack($data) {
        if (empty($data))
            return;
        $api = 'https://hooks.slack.com/services/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';
        $payload = array(
            "channel" => "#developer",
            "username" => "BlueOceanBot",
            "text" => "slack webhook"
        );
        if (is_string($data)) {
            $payload['text'] = $data;
        } elseif (is_array($data)) {
            $payload = array_merge($payload, $data);
        } else {
            return;
        }
        $post = "payload=" . json_encode($payload);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}
