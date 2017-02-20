<?php
class DB {
    //Параметры для подключения
    protected $hostname = "";
    protected $password = "";
    protected $user = "";
    protected $name = "";
    private $db;
	
	//Подключение
    public function connect() { 
        try {
            $this->db = new PDO('mysql:host='.$this->hostname.'; dbname='.$this->name, $this->user, $this->password); 
            return true;
        }
        catch (PDOException $e) {
            echo $e->getMessage();
        }
    }
    
	//Функция взаимодействия с rss
    public function rss_import()
    { 
    $rss = "http://lenta.ru/rss/news";
    $xml = @simplexml_load_file($rss);
    if ( $xml===false ) die('Error parse RSS: '.$rss);
	
    $i = 1;
    $n = 50;
    $statement = $this->db->prepare("INSERT INTO news(title, description, link, pubDate, image)
    VALUES(?,?,?,?,?)");

    foreach ( $xml->xpath('//item') as $item )
    {
	$stmt = $this->db->query("SELECT * FROM news WHERE link = '$item->link'");
    $row_count = $stmt->rowCount();


	if ($row_count == 0) {	
	  if($item->enclosure =! 0)
	  {
       $enclosure =  (string)$item->enclosure->attributes()->url;		
       $statement->execute(array($item->title,$item->description,$item->link,$item->pubDate = date("Y-m-d H:i:s"),$enclosure));	   
	  }
      else{
       $statement->execute(array($item->title,$item->description,$item->link,$item->pubDate = date("Y-m-d H:i:s"),""));  
	  }
	}
	     if($i>=$n) break;
	     $i++;
    }
    }
	
    //Функция вывода новостей
    function news_print()
    {
	 $stmt = $this->db->query('SELECT id,title FROM news ORDER BY id DESC LIMIT 50');

      while ($row = $stmt->fetch())
      {	  
	    echo '
         <div class="container">
            <div class="row">
                <div class="thumbnail">
				
                  <p>'.mb_substr($row['title'], 0, 200, 'UTF-8').'</p>
				  <a href="view_news.php?id='.$row["id"].'" class="btn btn-success btn-xs">Подробнее</a>
                </div>
            </div>
         </div>
         ';
      }
    }
	
    //Функция вывода подробной информации о новости
    function print_news($id_news)
    {
	 $stmt = $this->db->query("SELECT * FROM news WHERE id = '$id_news'");
	 $row = $stmt->fetch();
	
	 if ($row['image'] == "")
	  {
	   echo '
        <div class="container">
          <div class="row">
			   <div class="col-lg-12">
			   <h1>'.$row['title'].'</h1>
			   <p><h4>'.$row['description'].'</h4></p>
			   </div>
          </div>
        </div>
        ';
	   }
	  else
	  {
	   echo '
        <div class="container">
          <div class="row">
			   <div class="col-lg-5">
			     <img src="'.$row['image'].'" class="img-thumbnail" alt="Responsive image">
			   </div>
			   <div class="col-lg-7">
			   <h1>'.$row['title'].'</h1>
			   <p><h4>'.$row['description'].'</h4></p>
			   </div>
         </div>
       </div>
        ';
	  }
    }

    //Функция экспорта .csv файла
    function file_force_download()
     {
        $filename = $_SESSION['current_group'].'-'.date('d.m.Y').'.csv';
				
		$newdate = date('Y-m-d');

        $result_users = $this->db->prepare("SELECT title, pubDate, link FROM news WHERE pubDate LIKE '%".$newdate."%'");
        $result_users->execute(array($_SESSION['current_group_id']));

        $list = array ();

        while ($row = $result_users->fetch(PDO::FETCH_ASSOC)) {
        array_push($list, array_values($row));
        }
 
        $fp = fopen('php://output', 'w');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="'.$filename.'"');
        foreach ($list as $ferow) {
        fputcsv($fp, $ferow);
        }
      }
}
?>