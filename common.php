<?php

class MobitransfertCommons
{
 public $ServiceToken = "";//Obtain your own here https://mobitransfert.com/api/register	
 public $test = "OK";
 public function __construct($token)
    {
       $this->ServiceToken = $token; 
    } 
	
	
 private function send_request($Datas_) {
      $datas = (object) $Datas_;      
      $dest =  "https://api.mobitransfert.com:2020/users/$datas->action?";
      $query = "";
      if(count($Datas_)){
            foreach ($Datas_ as $key => $val){  
                $dest.=empty($query)?"$key=$val":"&$key=$val";  
            }         
      }  
    $ch = curl_init();
    $options = array(  
        CURLOPT_URL => $dest.$query,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_ENCODING => '',
        CURLOPT_AUTOREFERER => true,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_TIMEOUT => 120,
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_SSL_VERIFYPEER => 0
    );
    curl_setopt_array($ch, $options);            //  Fixe plusieurs options pour un transfert CURL
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($httpCode != 200) {
        curl_close($ch);
        return json_encode(["message" =>curl_error($ch),"code"=>$httpCode]);
    } else { 
        curl_close($ch);	
        return $response;
    }
}

 /*
  cette fonction permet de checker si une commande a été éxécutée et attends le message pour l'afficher au client
  Exemple il dira si l'utilisateur peux composer le #150# ou si son crédit est insuffisant ou s'il ya eu une érreur au niveau du système
  elle peut être appellée à tout moment;
  datas returned 
  for error => {error:{code:errorCode,message:ErrorMessage}}
  for success =>  {executedAt:"can be null if(not yet executed) or timestamp",message:"message from USSD server",errorCode :"Error code from USSD server can be 100(operator error) or 400(other errors) or 200(if success)" }   
  
  paramètres attendus:
  - Id de la transaction obtenue dans la réponse send_command
  $nouvelle_comm = new MobitransfertAPI($service_token,$prix,$qte,$phone);
  $json = $nouvelle_comm->send_command();<<= ICI
 */
 
  public function check_command($trID){
	 $datas = ["commandID"=>$trID,"action"=>"checkCommand","ServiceToken"=>$this->ServiceToken,"appID"=>$this->appID];
	 $response = $this->send_request($datas);
	 $await = 0;
	  while(!$response->error && ($response->executedAt=="null"||empty($response->executedAt))){
		sleep(1);
		$response = json_decode($this->send_request($datas));
        $await++;		
	 }
	 
	 return $response;	 
  }

  /*
  cette fonction permet de checker si un paiement a été éxécutée et attends le message pour l'afficher au client
  Exemple il dira si l'utilisateur a payé ou a annulé. 
  datas returned 
 for error => {error:{code:errorCode,message:ErrorMessage}}
 for success =>  {paid:"yes or no or null" }   
 
  paramètres attendus:
  - Id de la transaction obtenue dans la réponse send_command
  $nouvelle_comm = new MobitransfertAPI($service_token,$prix,$qte,$phone);
  $json = $nouvelle_comm->send_command();<<= ICI
  retourne
 */

  public function check_payment($trID){
	 $datas = ["commandID"=>$trID,"action"=>"checkPayment","ServiceToken"=>$this->ServiceToken,"appID"=>$this->appID];
	 $response = json_decode($this->send_request($datas));
	 $await = 0;
	 while(!$response->error&&($response->paid=="null"||empty($response->paid)||$response->paid=="no")&&$await<100){//on attends jusqu'à 5 minutes ie 3 x 100 secondes
		sleep(1);
		$response = json_decode($this->send_request($datas));
        $await++;		
	 }
	 
	 return $response;	 
  }

}
