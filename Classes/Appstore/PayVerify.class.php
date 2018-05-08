<?php
/**
 * appstore 支付验证
 *
 *
 */

namespace Classes\Appstore;

class PayVerify
{
    const URL = 'https://buy.itunes.apple.com/verifyReceipt';
    const SANDBOX_URL = 'https://sandbox.itunes.apple.com/verifyReceipt';

    /**
     * 验证receipt数据
     * @param $receipt
     * @param bool $is_sandbox
     * @return array|int < 0 表示需要向沙箱环境发起验证，> 0表示请求失败，array 验证后的商品信息
     */
    public static function verify($receipt, $is_sandbox = false)
    {
        $post_params = [
            'receipt-data' => $receipt
        ];

        if ($is_sandbox) {
            $ch = curl_init(self::SANDBOX_URL);
        } else {
            $ch = curl_init(self::URL);
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($post_params));

        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);  //这两行一定要加，不加会报SSL 错误
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);


        $response = curl_exec($ch);
        $errno = curl_errno($ch);
        $errmsg = curl_error($ch);
        curl_close($ch);

        //判断时候出错，抛出异常
        if ($errno != 0) {
            debug_log("verify receipt[{$receipt}] failed, curl error: {$errno}, {$errmsg}", JF_LOG_ERROR);
            return 1;
        }

        $data = json_decode($response);

        //判断返回的数据是否是对象
        if (!is_object($data)) {
            debug_log("verify receipt[{$receipt}] failed, response data error, {$response}", JF_LOG_ERROR);
            return 2;
        }
        //判断购买时候成功
        if (!isset($data->status) || $data->status != 0) {
            debug_log("verify receipt failed[{$receipt}], status: [{$data->status}]", JF_LOG_ERROR);
            if ($data->status == 21007) {
                // need 需要向沙箱环境请求验证
                return -1;
            }
            return 3;
        }
        //返回产品的信息
        try {
            $receipt_info = $data->receipt->in_app[0];

            return array(
                'quantity' => $receipt_info->quantity,
                'productId' => $receipt_info->product_id,
                'transactionId' => $receipt_info->transaction_id,
                'purchaseDate' => $receipt_info->purchase_date,
                'appItemId' => $receipt_info->app_item_id,
                'bid' => $receipt_info->bid,
                'bvrs' => $receipt_info->bvrs
            );
        } catch (\Exception $exception) {
            debug_log(['IosPayVerify', $data, $exception], JF_LOG_ERROR);
            return 4;
        }
    }
}