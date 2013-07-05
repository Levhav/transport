<?php

/**
 * Отправляет http запрос и возвращает ответ.
 * @version 1.0
 */
class transport
{
 var $version=2.11;
    /**
     * @var string URL
     */
 var $url='';

 /**
  * Отображать заголовки ответа в теле ответа
  * @var bool 
  */
 var $showHeader=false;
 /**
  *
  * @var string Данные для POST
  */
 var $message=NULL;
 /**
  * Для того чтоб данные у принимающей стороны попали в $GLOBALS['HTTP_RAW_POST_DATA'] в ContentType надо установить 'application/octet-stream';
  * @var string Content Type
  */
 var $ContentType=false;
 
 /**
  * Масив заголовков которые будут отправлены
  * @var Array 
  */
 var $header=Array("Content-Type"=>"application/x-www-form-urlencoded");
 
 /**
  * @var strind Имя пользователя
  */
 var $Name='';

 /**
  * @var string Пароль пользователя
  */
 var $Password='';

 /**
  * @var string Пароль пользователя
  */
 var $AUTHENTICATE_TYPE=0;
     
  /**
   * Если не false то включает запись cookie в файл с именем указаным как значение $this->cookie_file
   * @var fileName 
   */
  var $cookie_file=false;
  
  /**
   * Если не false то включает запись cookie в файл с именем указаным как значение $this->cookie_file
   * @var fileName 
   */
  var $cookie_file_in=false;
  
  /**
   * Если не false то включает запись cookie в файл с именем указаным как значение $this->cookie_file
   * @var fileName 
   */
  var $cookie_file_to=false;
  
  /**
   * Если не false то включает работу через прокси и должно содержать ip и порт прокси (на пример "195.175.37.72:80")
   * @var fileName 
   */
  var $http_proxy=false;
  
  var $http_proxy_type = CURLPROXY_SOCKS5;
  
  /**
   * Таймаут соеденения
   * @var timeOut int
   */
  var $timeOut = 3;
  
  /**
   * Сообщение об ошибке
   * @var string 
   */
  var $error;
  
  /**
   * Код ошибки
   * @var int 
   */
  var $error_code;
  
 /**
  * Отправляет http запрос и возвращает ответ.
  * @param string $url Урл, если пуст будет взят $this->url
  * @param string $message Данные для POST, если не указан будет взято из  $this->message. Если и там пусто то будет отправлен GET запрос
  * @param string $refer Содержимое заголовка refer
  * @return string
  * @version 1.0
  */
 function request($url='',$message=NULL,$refer=false)
 {
     if($this->http_proxy===true)
     {
         return $this->ProxyRequest(false, $url, $message, $refer);
     }
      
   if(is_array($message))
   {
       $t="";
       foreach ($message as $key => $value)
       {
           $t[]=$key."=".$value;
       }
    $message=implode("&", $t); 
   }
   
   if(!empty($url))
   {
       $this->url=$url;
   }
   else
   {
       return LOG::send_error("Произошла ошибка в transport->request()<br>Целевой URL пуст.");
   }

   if($message!=NULL)
   {
       $this->message=$message;
   }

      if($this->AUTHENTICATE_TYPE==1 && !empty($this->Name))
      {
          $url.="/UserLogin/".$this->Name."/UserPassword/".$this->Password;
      }
       //echo $this->url;
       $curl = curl_init();
       if( $curl )
       {
        curl_setopt($curl,CURLOPT_URL,$url);

        // Нужно помнить куки
        curl_setopt($curl, CURLOPT_COOKIESESSION, TRUE);
       // curl_setopt($curl, CURLOPT_COOKIEFILE, "cookiefile");

        if($this->AUTHENTICATE_TYPE!=1 && !empty($this->Name)){curl_setopt($curl, CURLOPT_USERPWD,$this->Name.":".$this->Password );}
        // Скачанный код возвращаем в переменную а не в поток
        curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);

        // "Следовать туда, куда зовут". Если сервис выдает 302 код, мы следуем по этой ссылке
        curl_setopt($curl,CURLOPT_FOLLOWLOCATION,true); // Возможно будет не работать на некоторых хостингах

        // Таймаут, если сервис не отвечает больше XX секунд, выходим
        curl_setopt($curl,CURLOPT_CONNECTTIMEOUT,$this->timeOut);

        if( $this->ContentType!=false ){ $this->header['Content-Type']=$this->ContentType;}
        
        $http_header=Array();
        foreach ($this->header as $key => $value)
        {
            $http_header[count($http_header)]=$key.": ".$value;
        }
        
        curl_setopt($curl, CURLOPT_HTTPHEADER, $http_header);

        if(!isset($this->header['User-Agent'])){curl_setopt($curl,CURLOPT_USERAGENT,"Machaon cms from ".getConfig("hostname"));} // Напишем в юзер-агент

        if($this->message!=NULL)
        { //echo $this->message;
          // Указываем подключению, что слать нужно не GET (по умолчанию), а POST запросы
          curl_setopt($curl,CURLOPT_POST,TRUE);

          // Указываем, что именно отправлять в POST
          curl_setopt($curl,CURLOPT_POSTFIELDS,$this->message);
        }

           if($refer!=false)
           {    
            curl_setopt($curl, CURLOPT_AUTOREFERER,1);
            curl_setopt($curl, CURLOPT_REFERER, $refer);
           } 
           
           curl_setopt($curl, CURLOPT_HEADER, $this->showHeader); 
            
           if($this->cookie_file!=false)
           {
              // echo "!".$this->cookie_file;
             curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie_file);
             curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie_file);
           }
           
           if($this->http_proxy!=false)
           {
             curl_setopt($curl, CURLOPT_HTTPPROXYTUNNEL, 1); 
             curl_setopt($curl, CURLOPT_PROXY, $this->http_proxy);
             curl_setopt($curl, CURLOPT_PROXYTYPE, $this->http_proxy_type);
           } 
           
           if($this->cookie_file_in!=false)
           {
             curl_setopt($curl, CURLOPT_COOKIEJAR, $this->cookie_file);
           }
       
           if($this->cookie_file_to!=false)
           {
             curl_setopt($curl, CURLOPT_COOKIEFILE, $this->cookie_file);
           }
         // Если все ок, в $html вернется ответ
         $html = curl_exec($curl);
         
        if($html)
        {
         // Закрываем подключение, очищаем память
         curl_close($curl);
         $this->error_code = 0;
         $this->error = "";
         
         return $html;
        }
       }
       else
       {
        $this->error ="Произошла ошибка в transport->request()<br>curl_init() вернул false";
        $this->error_code = -2;
       return false;
       }
       
      $this->error ="Произошла ошибка в transport->request()<br>Возможно не получены данные от $url\n".curl_error($curl);
      $this->error_code = -1;
     return -1;
 }
 
 /**
  * Добавляет заголовки которые будут отправлены при вызове $this->request
  * @param type $name Имя заголовка, если сёда передать асоциативный масив то можно разом установить много заголовков и при этом второй параметр не требуется.
  * @param type $value Значение заголовка
  */
 function addHeader($name,$value=false)
 {
     if(is_array($name))
     {
      $this->header = array_merge($this->header, $name);
     }
     else
     {
         $this->header[$name]=$value;
     }
 }
 
 
 /**
  * Задержка после полного прохода по всем доступным прокси серверам
  * Если ноль то если в функции закончатся доступные прокси то функция ProxyRequest вернёт false 
  * Если не ноль то если в функции закончатся доступные прокси то функция ProxyRequest подождёт $proxy_sleep секунд и начнёт проверку списка прокси с начала
  * 
  * @var int
  */
 var $proxy_sleep = 0;
 
 /**
  * Выполняет запрос через прокси из списка заданого в таблице прокси
  * @param function $callBack Проверяет ответ и должна вернуть true если ответ коректный и false если необходимо использовать другой прокси для запроса
  * @param string $url
  * @param string $message
  * @param string $refer
  * @return string  
  * 
  * @version 1.0
  */
 function ProxyRequest($callBack=false,$url='',$message=NULL,$refer=false)
 {
     if($callBack===false)
     {
         $callBack = "ProxyRequest_defaultCallbak";
     }
      
     if($this->http_proxy!==false && $this->http_proxy!==true)
     {
        $response = $this->request($url,$message,$refer);
        if($response!=-1)
        {
            if(!$callBack($response))
            {
                Controller::dbManager()->prepare("UPDATE `proxy` SET  `status`= 403 WHERE `id`='".$this->http_proxy."' ")->execute();
            }
            else
            {
                Controller::dbManager()->prepare("UPDATE `proxy` SET  `page`= `page`+1 WHERE `id`='".$this->http_proxy."' ")->execute();
                return $response; 
            }
        }
        else
        {
            Controller::dbManager()->prepare("UPDATE `proxy` SET  `status` = 500 WHERE `id`='".$this->http_proxy."' ")->execute();
        }
     }
        
      while(1)
      {
           $re = Controller::dbManager()->prepare("SELECT id,type FROM `proxy` WHERE status  in (0,200)  ORDER BY   `page` DESC limit 1 ")->execute();
           if($re->isValid())
           {
               $row = $re->fetch_array();
               echo "set-http_proxy:".$row['id']."\n";
               $this->http_proxy = $row['id'];
               
               if($row['type']==4)
               {
                   $this->http_proxy_type = CURLPROXY_SOCKS4;
               }
               else
               {
                   $this->http_proxy_type = CURLPROXY_SOCKS5;
               }

               Controller::dbManager()->prepare("UPDATE `proxy` SET  `status`= '".Controller::getID()."' WHERE `id`='".$this->http_proxy."' ")->execute();
               $response = $this->request($url,$message,$refer);
               
               if($response!=-1)
               {
                   if(!$callBack($response))
                   {
                       Controller::dbManager()->prepare("UPDATE `proxy` SET  `status`= 403 WHERE `id`='".$this->http_proxy."' ")->execute();
                   }
                   else
                   {
                       Controller::dbManager()->prepare("UPDATE `proxy` SET   `page`= `page`+1 WHERE `id`='".$this->http_proxy."' ")->execute();
                       return $response; 
                   }
               }
               else
               {
                   Controller::dbManager()->prepare("UPDATE `proxy` SET  `status` = 500 WHERE `id`='".$this->http_proxy."' ")->execute();
               }
           }
           else
           {
               $this->error_code = -3;
               $this->error = "Нет пригодных прокси серверов\n";

               Controller::dbManager()->prepare("UPDATE `proxy` SET `status`=0 WHERE `status` < 1000 ")->execute();
               if($this->proxy_sleep>0)
               {
                   sleep($this->proxy_sleep);
               }
               else
               {
                   return false;
               }
           } 
      }
 }
 
 function __destruct()
 {
    if($this->http_proxy!==false)
    {
        Controller::dbManager()->prepare("UPDATE `proxy` SET  `status`= 200 WHERE `id`='".$this->http_proxy."' and `status` > 999 ")->execute();
    }
 }
 
}

 function ProxyRequest_defaultCallbak()
 {
     return true;
 }
?>
