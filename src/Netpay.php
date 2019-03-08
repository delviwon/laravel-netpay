<?php
namespace Lewee\Netpay;

use App\Exceptions\InternalException;
use App\Handlers\RequestHandler;

class Netpay
{
    const URL = 'http://sfj.chinapay.com';
    const HEADER = [
        'Content-Type' => 'application/x-www-form-urlencoded',
    ];

    /**
     * Netpay constructor.
     */
    public function __construct()
    {
        require __DIR__ . '/netpayclient7.php';
    }

    /**
     * 导入私钥
     * @return bool
     * @throws InternalException
     */
    public function importKey()
    {
        $merid = @buildKey( __DIR__ . '/MerPrk.key');

        if(!$merid) {
            throw new InternalException('导入私钥文件失败');
        }

        return $merid;
    }

    /**
     * 发起代付
     */
    public function pay($data)
    {
        $merid = $this->importKey();

        $params = [
            'merId' => $merid,
            'merDate' => date('Ymd'),
            'merSeqId' => $data['order_id'],
            'cardNo' => $data['card_no'],
            'usrName' => $data['account_name'],
            'openBank' => $data['bank_name'],
            'prov' => $data['province'],
            'city' => $data['city'],
            'transAmt' => $data['amount'],
            'purpose' => $data['purpose'],
            'flag' => '00',
            'version' => '20160530',
            'signFlag' => 1,
            'termType' => '08',
            'payMode' => 0,
        ];

        $chkValueStr = '';

        foreach ($params as $key => $param) {
            if ($key === 'signFlag') {
                continue;
            }

            $chkValueStr .= $param;
        }

        $params['chkValue'] = sign(base64_encode($chkValueStr));

        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $url = 'http://sfj.chinapay.com/dac/SinPayServletUTF8';
        $result = RequestHandler::request('POST', $url, $headers, $params, 'form_params');
        $code = $result['responseCode'] ?? null;

        if ($code === null) {
            throw new InternalException('代付请求失败');
        }

        return $code;
    }

    /**
     * 查询订单
     */
    public function query($order_id)
    {
        $date = substr(date('Y'), 0, 2) . substr($order_id, 0, 6);
        $merid = $this->importKey();

        $params = [
            'merId' => $merid,
            'merDate' => $date,
            'merSeqId' => $order_id,
            'version' => '20090501',
            'signFlag' => 1,
        ];

        $chkValueStr = '';

        foreach ($params as $key => $param) {
            if ($key === 'signFlag') {
                continue;
            }

            $chkValueStr .= $param;
        }

        $params['chkValue'] = sign(base64_encode($chkValueStr));
        $url = self::URL . '/dac/SinPayQueryServletUTF8';
        $result = RequestHandler::request('POST', $url, self::HEADER, $params, 'form_params');
        $code = $result[0] ?? null;

        if ($code === null) {
            throw new InternalException('代付订单查询失败');
        }

        return $code;
    }

    /**
     * 查询备付金
     * @return mixed
     * @throws \App\Exceptions\InternalException
     */
    public function provisions()
    {
        $merid = $this->importKey();

        $params = [
            'merId' => $merid,
            'version' => '20090501',
            'signFlag' => 1,
        ];

        $chkValueStr = '';

        foreach ($params as $key => $param) {
            if ($key === 'signFlag') {
                continue;
            }

            $chkValueStr .= $param;
        }

        $params['chkValue'] = sign(base64_encode($chkValueStr));
        $url = self::URL . '/dac/BalanceQueryUTF8';
        $result = RequestHandler::request('POST', $url, self::HEADER, $params, 'form_params');
        $code = $result[0] ?? null;

        if ($code === null || $code !== '000') {
            throw new InternalException('备付金查询失败');
        }

        return $result[2] / 100;
    }
}
