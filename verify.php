<?php
	// Helper method to get a string description for an HTTP status code
    // From http://www.gen-x-design.com/archives/create-a-rest-api-with-php/ 
	function getStatusCodeMessage($status)
    {
        // these could be stored in a .ini file and loaded
        // via parse_ini_file()... however, this will suffice
        // for an example
        $codes = Array(
            100 => 'Continue',
            101 => 'Switching Protocols',
            200 => 'OK',
            201 => 'Created',
            202 => 'Accepted',
            203 => 'Non-Authoritative Information',
            204 => 'No Content',
            205 => 'Reset Content',
            206 => 'Partial Content',
            300 => 'Multiple Choices',
            301 => 'Moved Permanently',
            302 => 'Found',
            303 => 'See Other',
            304 => 'Not Modified',
            305 => 'Use Proxy',
            306 => '(Unused)',
            307 => 'Temporary Redirect',
            400 => 'Bad Request',
            401 => 'Unauthorized',
            402 => 'Payment Required',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            406 => 'Not Acceptable',
            407 => 'Proxy Authentication Required',
            408 => 'Request Timeout',
            409 => 'Conflict',
            410 => 'Gone',
            411 => 'Length Required',
            412 => 'Precondition Failed',
            413 => 'Request Entity Too Large',
            414 => 'Request-URI Too Long',
            415 => 'Unsupported Media Type',
            416 => 'Requested Range Not Satisfiable',
            417 => 'Expectation Failed',
            500 => 'Internal Server Error',
            501 => 'Not Implemented',
            502 => 'Bad Gateway',
            503 => 'Service Unavailable',
            504 => 'Gateway Timeout',
            505 => 'HTTP Version Not Supported'
        );
    
        return (isset($codes[$status])) ? $codes[$status] : '';
    }
    
    // Helper method to send a HTTP response code/message
    function sendResponse($status = 200, $body = '', $content_type = 'text/html')
    {
        $status_header = 'HTTP/1.1 ' . $status . ' ' . getStatusCodeMessage($status);
        header($status_header);
        header('Content-type: ' . $content_type);
        echo $body;
    }
	
class BakerShelfAPI
{
	// Validate InApp Purchase Receipt
    public function verifyReceipt($device, $receipt, $isSandbox = true)
    {
        if ($isSandbox) {
            $endpoint = 'https://sandbox.itunes.apple.com/verifyReceipt';
        }
        else {
            $endpoint = 'https://buy.itunes.apple.com/verifyReceipt';
        }
  
        $postData = json_encode(
            array('receipt-data' => $receipt)
        );
		  
		$ch = curl_init($endpoint);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
 
        $response = curl_exec($ch);
        $errno    = curl_errno($ch);
        $errmsg   = curl_error($ch);
        curl_close($ch);
		
		if ($errno != 0) {
            throw new Exception($errmsg, $errno);
        }

        $data = json_decode($response);

        if (!is_object($data)) {
            throw new Exception('Invalid response data');
        }
 
        if (!isset($data->status) 
		|| $data->status != 0) 
		{
            throw new Exception('Invalid receipt');
        }
		
		return array(
            'quantity'       =>  $data->receipt->quantity,
            'product_id'     =>  $data->receipt->product_id,
            'transaction_id' =>  $data->receipt->transaction_id,
            'purchase_date'  =>  $data->receipt->purchase_date,
            'app_item_id'    =>  $data->receipt->app_item_id,
            'bid'            =>  $data->receipt->bid,
            'bvrs'           =>  $data->receipt->bvrs
        );
    }
}

$device    = $_POST['device_id'];
$receipt   = $_POST['receipt'];
$isSandbox = (bool) $_POST['sandbox'];

$api = new BakerShelfAPI();

try 
{
   if(strpos($receipt,'{'))
   {
      $receipt = base64_encode($receipt);
   }
        
   $result = $api->verifyReceipt($device, $receipt, $isSandbox);
   sendResponse(200, json_encode($result));
}
catch (Exception $ex)
{
   sendResponse(400, $ex->getMessage());
}

?>
