<?php
/**
 *  易支付-支付宝接口
 *  Author : Alone88
 * Date: 2019-8-9
 * web : https://alone88.cn
 *
 */

namespace Pay\alpayalipay;

use \Pay\notify;

class alpayalipay
{

    private $paymethod = "alpayalipay";

    //处理请求
    public function pay($payconfig, $params)
    {

        try {

            $config = array(
                //商户id
                'pid' => $payconfig['app_id'],
                //支付类型
                'type' => 'alipay',
                //系统订单号
                'out_trade_no' => $params['orderid'],
                // 商品名称
                'name' => $params['productname'],
                //商品金额
                'money' => (float)$params['money'],
                //网站名称
                'sitename' => $params['webname'],
                //异步通知地址
                'notify_url' => $params['weburl'] . '/product/notify/?paymethod=' . $this->paymethod,
                //异步跳转地址
                'return_url' => $params['weburl'] . "/query/auto/{$params['orderid']}.html"
            );
            //排序数组
            $config = $this->argSort($config);
            // 转换成参数状态
            $prestr = $this->createLinkstring($config);
            //加上密钥
            $data = md5($prestr . $payconfig['app_secret']);
            $config['sign'] = $data;
            $config['sign_type'] = strtoupper('MD5');

            //获取url
            $url = $payconfig['configure3'] . 'submit.php?' . $this->createLinkstring($config);
            if($url){
                $result = array('type' => 1, 'subjump' => 0, 'paymethod' => $this->paymethod, 'url' => $url, 'payname' => $payconfig['payname'], 'overtime' => $payconfig['overtime'], 'money' => $params['money']);
                return array('code' => 1, 'msg' => 'success', 'data' => $result);
            }else{
                return array('code'=>1001,'msg'=>'支付接口请求失败','data'=>'');
            }
        } catch (\Exception $e) {
            return array('code' => 1000, 'msg' => $e->getMessage(), 'data' => '');
        }
    }

    //处理回调
    public function notify($payconfig)
    {
        try {
            //获取传入数据
            $params = $_GET;
            //去除空值和签名参数
            $params = $this->paraFilter($params);
            //排序
            $params = $this->argSort($params);
            //签名
            $md5Sigm = md5($this->createLinkstring($params) . $payconfig['app_secret']);
            // 验证签名数据
            if ($md5Sigm == $_GET['sign'] && $params['trade_status'] == 'TRADE_SUCCESS') {
                //成功
                //商户订单号
                $config = array('paymethod' => $this->paymethod, 'tradeid' => $params['trade_no'], 'paymoney' => $params['money'], 'orderid' => $params['out_trade_no']);
                $notify = new \Pay\notify();
                $data = $notify->run($config);
                if ($data['code'] > 1) {
                    return 'error|Notify: ' . $data['msg'];
                } else {
                    return 'success';
                }
            } else {
                return 'error|Notify: auth fail';
            }

        } catch (\Exception $e) {
            file_put_contents(YEWU_FILE, CUR_DATETIME . '-' . $e->getMessage() . PHP_EOL, FILE_APPEND);
            exit;
        }
    }


    /**
     * 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
     * @param $para 需要拼接的数组
     * return 拼接完成以后的字符串
     */
    function createLinkstring($para)
    {
        $arg = "";
        while (list ($key, $val) = each($para)) {
            $arg .= $key . "=" . $val . "&";
        }
        //去掉最后一个&字符
        $arg = substr($arg, 0, count($arg) - 2);

        //如果存在转义字符，那么去掉转义
        if (get_magic_quotes_gpc()) {
            $arg = stripslashes($arg);
        }
        return $arg;
    }

    /**
     * 除去数组中的空值和签名参数
     * @param $para 签名参数组
     * return 去掉空值与签名参数后的新签名参数组
     */
    function paraFilter($para)
    {
        $para_filter = array();
        while (list ($key, $val) = each($para)) {
            if ($key == "sign" || $key == "sign_type" || $val == "" || $key == 'paymethod') continue;
            else    $para_filter[$key] = $para[$key];
        }
        return $para_filter;
    }
    /**
     * 对数组排序
     * @param $para 排序前的数组
     * return 排序后的数组
     */
    function argSort($para)
    {
        ksort($para);
        reset($para);
        return $para;
    }
}
