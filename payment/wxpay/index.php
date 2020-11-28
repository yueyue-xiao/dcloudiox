<?php
header('Access-Control-Allow-Origin: *');
header('Content-type: text/plain');

require_once "RequestHandler.class.php";
require_once "ResponseHandler.class.php";
require "TenpayHttpClient.class.php";


$DEBUG_ = false;
//�Ƹ�ͨ�̻���
$PARTNER = "%�̻���(PartnerID)%";
//�Ƹ�ͨ��Կ
$PARTNER_KEY = "%��ʼ��Կ(PartnerKey)%";
//appid
$APP_ID="%΢�ſ���ƽ̨Ӧ�õ�AppID%";
//appsecret
$APP_SECRET= "%΢�ſ���ƽ̨Ӧ�õ�AppSecret%";   
//paysignkey(��appkey)
$PaySignKey="%΢�ſ���ƽ̨Ӧ������֧�����ܺ��ȡ��֧��ר��ǩ����PaySignKey%";
//֧����ɺ�Ļص�����ҳ��
$notify_url = "http://demo.dcloud.net.cn/payment/wxpay/notify.php";


// ��ȡ֧�����
$amount='';
if($_SERVER['REQUEST_METHOD']=='POST'){
    $amount=$_POST['total'];
}else{
    $amount=$_GET['total'];
}
$total = floatval($amount);
if(!$total){
    $total = 1;
}
$total = $total*100;     // ת�ɷ�

// ��Ʒ����
$subject = 'DCloud��Ŀ����';
// �����ţ�ʾ������ʹ��ʱ��ֵ��ΪΨһ�Ķ���ID��
$out_trade_no = date('YmdHis', time());


$outparams =array();
//��ȡtokenֵ
$reqHandler = new RequestHandler();
$reqHandler->init($APP_ID, $APP_SECRET, $PARTNER_KEY, $PaySignKey);
// 1Сʱ����һ��Token
$tokenPath = 'token.txt';
$tokenMTime = filemtime($tokenPath);
$curTime = time();
$Token = file_get_contents($tokenPath);
if(abs($curTime-$tokenMTime)>3600){
    $Token= $reqHandler->GetToken();
    file_put_contents($tokenPath,$Token);
}
if ( $Token !='' ){
    //=========================
    //����Ԥ֧����
    //=========================
    //����packet֧������
    $packageParams =array();        
    
    $packageParams['bank_type']     = 'WX';             //֧������
    $packageParams['body']          = $subject;         //��Ʒ����
    $packageParams['fee_type']      = '1';              //���б���
    $packageParams['input_charset'] = 'GBK';            //�ַ���
    $packageParams['notify_url']    = $notify_url;      //֪ͨ��ַ
    $packageParams['out_trade_no']  = $out_trade_no;    //�̻�������
    $packageParams['partner']       = $PARTNER;         //�����̻���
    $packageParams['total_fee']     = $total;           //��Ʒ�ܽ��,�Է�Ϊ��λ
    $packageParams['spbill_create_ip']= $_SERVER['REMOTE_ADDR'];  //֧������IP
    //��ȡpackage��
    $package= $reqHandler->genPackage($packageParams);
    $time_stamp = time();
    $nonce_str = md5(rand());
    //����֧������
    $signParams =array();
    $signParams['appid']    =$APP_ID;
    $signParams['appkey']   =$PaySignKey;
    $signParams['noncestr'] =$nonce_str;
    $signParams['package']  =$package;
    $signParams['timestamp']=$time_stamp;
    $signParams['traceid']  = 'mytraceid_001';
    //����֧��ǩ��
    $sign = $reqHandler->createSHA1Sign($signParams);
    //���ӷǲ���ǩ���Ķ������
    $signParams['sign_method']      ='sha1';
    $signParams['app_signature']    =$sign;
    //�޳�appkey
    unset($signParams['appkey']); 
    //��ȡprepayid
    $prepayid=$reqHandler->sendPrepay($signParams);

    if ($prepayid != null) {
        $pack   = 'Sign=WXPay';
        //��������б�
        $prePayParams =array();
        $prePayParams['appid']      =$APP_ID;
        $prePayParams['appkey']     =$PaySignKey;
        $prePayParams['noncestr']   =$nonce_str;
        $prePayParams['package']    =$pack;
        $prePayParams['partnerid']  =$PARTNER;
        $prePayParams['prepayid']   =$prepayid;
        $prePayParams['timestamp']  =$time_stamp;
        //����ǩ��
        $sign=$reqHandler->createSHA1Sign($prePayParams);

        $outparams['retcode']=0;
        $outparams['retmsg']='ok';
        $outparams['appid']=$APP_ID;
        $outparams['noncestr']=$nonce_str;
        $outparams['package']=$pack;
        $outparams['partnerid']=$PARTNER;
        $outparams['prepayid']=$prepayid;
        $outparams['timestamp']=$time_stamp;
        $outparams['sign']=$sign;

    }else{
        $outparams['retcode']=-2;
        $outparams['retmsg']='���󣺻�ȡprepayIdʧ��';
    }
}else{
    $outparams['retcode']=-1;
    $outparams['retmsg']='���󣺻�ȡ����Token';
}

    //Json ���
    ob_clean();
    echo json_encode($outparams);
    //debug��Ϣ,ע��������������ַ�����ҪJsEncode
    if ($DEBUG_ ){
        echo PHP_EOL  .'/*' . ($reqHandler->getDebugInfo()) . '*/';
    }

?>