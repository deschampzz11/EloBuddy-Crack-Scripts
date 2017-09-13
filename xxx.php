<?php 
	
	///////////////////////////////////////////////////
	///			KONFIGURACJA - START 				///
	///////////////////////////////////////////////////
	
	// Pobiera potrzebne funkcje
	include('functions.php');
	
	// IP SERWERA
	define('ts_query_ip', '192.166.219.28');
	
	// PORT SERWERA
	define('ts_server_port', '9987');
	
	// PORT SERVERQUERY
	define('ts_query_port', '10011');
	
	// NAZWA UŻYTKOWNIKA SERVERQUERY
	define('ts_query_username', 'serveradmin');
	
	// HASŁO UŻYTKOWNIKA SERVERQUERY
	define('ts_query_password', 'ddddd');
	
	// ŚCIEŻKA DO FRAMEWORKA
	define('ts3framework', '/var/www/ts3phpframework-master/libraries/TeamSpeak3/TeamSpeak3.php');
	
	// GRUPY ADMINISTRACYJNE [przedzielone przecinkiem]
	define('ts_sgid_admins', '10,11,12,13,14,15,16');
	
	// GRUPY WYKLUCZONE ZE ZLICZANIA [przedzielone przecinkiem]
	define('ts_sgid_skip_count', '1,2');
	
	// ODSTĘP ŁĄCZENIA SIĘ Z SERWEREM I POBRANIA DANYCH
	define('connect_ts_interval_sec', '60');

	///////////////////////////////////////////////////
	///			KONFIGURACJA - KONIEC 				///
	///////////////////////////////////////////////////

	// Zabezpieczenie dla początkujących
	file_exists(ts3framework) ? require_once ts3framework : exit("<meta charset='utf-8'><h2>Nie znaleziono biblioteki TS3 PHP Framework.<br><small>Możesz ją pobrać z <a href='https://github.com/planetteamspeak/ts3phpframework'>GitHub - TeamSpeak 3 PHP Framework</a></small></h2>Sprawdź ścieżkę w konfiguracji.<br>Podana ścieżka przez Ciebie jest błędna: <code style='background: #FFEB3B'>".ts3framework."</code>");
	
	// Pobiera dane z pliku
	$file = json_decode(file_get_contents('stats.json'), true);
	
	// Porównuje czas z pliku, do konfiguracyjnych danych
	$need_ts = ((time() - $file['time']) > connect_ts_interval_sec) ? true : false;
	
	// Tworzy tablicę na statystyki
	$stats = array('admins' => 0, 'clients' => 0, 'real_clients' => 0);
	
	
	if($need_ts){
		
		// Łączy z serwerem TeamSpeak 3
		try{
			$ts = TeamSpeak3::factory("serverquery://".ts_query_username.":".ts_query_password."@".ts_query_ip.":".ts_query_port."/?server_port=".ts_server_port);
		}
		catch(Exception $e){
			// Przypisuje błąd do $e 
			echo '<meta charset="utf-8"><h1>Nie można połączyć się z serwerem, sprawdź poprawność danych konfiguracyjnych</h1>';
			// Wyświetla kod błędu oraz jego treść i kończy skrypt
			exit('Kod błędu: <b>'.$e->getCode().'</b> oraz treść błędu: <b>'.$e->getMessage().'</b>');
		}
		
		// Pobiera listę klientów
		$clients = $ts->clientList();
		// Pobiera listę kanałów
		$channels = $ts->channelList();
		
		// Dodaje wpis do tablicy z liczbą kanałów
		$stats['channels'] = count($channels);
		// Dodaje wpis do tablicy z aktualnym czasem
		$stats['time'] = time();
		
		// Pętla do liczenia klientów
		foreach($clients as $client){
			
			// Wszyscy klienci
			$stats['clients']++;
			
			// Pomija wybrane rangi
			if(empty(array_intersect(explode(',', ts_sgid_skip_count), explode(',', $client['client_servergroups'])))){
				$stats['real_clients']++;
			}
			
			// Szuka administratorów
			if(!empty(array_intersect(explode(',', ts_sgid_admins), explode(',', $client['client_servergroups'])))){
				$stats['admins']++;
			}
		}
		// Wrzuca do pliku potrzebne dane, kodując je w JSON
		file_put_contents('stats.json', json_encode($stats), LOCK_EX);
	}
	else{
		$stats = $file;
	}
		
	// Tworzy obraz z pliku 
	$image = imagecreatefrompng('tlo.png');
	
	putenv('GDFONTPATH=' . realpath('.'));
	
	// Tworzy kolory
	$white = imagecolorallocate($image, 255, 255, 255);
	$black = imagecolorallocate($image, 0, 0, 0);
	
	// Definiuje czcionki
	$font1 = 'RopaSans-Regular';
	$font2 = 'JosefinSans-Regular.ttf';
	
	// Funkcja do pisania od prawej strony
	function imagettftextright($img, $fontsize, $angle, $x, $y, $color, $fontname, $text){
		$dimensions = imagettfbbox($fontsize, $angle, $fontname, $text);
		$textWidth = abs($dimensions[4] - $dimensions[0]);
		$x_right = imagesx($img) - $textWidth;
		imagettftext($img, $fontsize, $angle, $x_right-$x, $y, $color, $fontname, $text);
	}
	
	// Pisze na obrazie z prawej strony
	imagettftextright($image, 110, 0, 5, 135, $white, $font1, $stats['real_clients']);
	imagettftextright($image, 20, 0, 10, 30, $white, $font2, 'Użytkownicy online');
	
	// Pisze na obrazie z lewej strony
	imagettftext($image, 20, 0, 10, 30, $white, $font2, 'Administratorzy online: '.$stats['admins']);
	imagettftext($image, 45, 0, 10, 82, $white, $font1, 'godz. '.date('H:i'));
	imagettftext($image, 17, 0, 10, 115, $white, $font1, polski_dzien().', '.date('d').' '.polski_miesiac().' '.date('Y'));
	imagettftext($image, 16, 0, 10, 140, $white, $font1, imieniny());
	//imagettftext($image, 20, 0, 10, 140, $white, $font3, );
	
	// Wyświetla stronę jako obraz
	header('Content-Type: image/png');
	
	// Wyświetla obraz
	imagepng($image);
	
	// Niszczy obraz (uwalnia pamięć)
	imagedestroy($image);
	

?>
