<?php

	/*Es wird überprüft, ob bereits Daten im Frontend abgeschickt wurden. 
	Ist dies nicht der Fall, wird das Formular angezeigt.*/
	if(!isset($_POST["abgeschickt"]))
	{
		
?>
		<form action="neuer_kaffeetrinker.php" method="post">
		Vorname<br>
		<input type="Text" name="vorname" size="20"><br>
		Nachname<br>
		<input type="Text" name="nachname" size="20"><br>
		Personalnummer<br>
		<input type="Text" name="personalnummer" size="20"><br>
		Kaffees (Initialwert)<br>
		<input type="Text" name="kaffees" size="20" value="0"><br>
		<input type="Submit" value="Kaffeetrinker anlegen" name="abgeschickt">
		</form>
		
<?php		
		
	}	
	
	/*Es wurden Daten vom Frontend aus abgeschickt. 
	Diese werden jetzt verarbeitet.*/
	else 
	{
		/*WICHTIG: Sollte noch als Funktion abgekapselt werden!*/
		/*Zugangsdaten zur Datenbank, sowie Tabellennamen werden aus den Einstellungen ausgelesen.*/
		$config = file("einstellungen");	
		foreach($config AS $zeile)
		{
			/*Ausschließlich A .. Z und a .. z sind in der Datei als erster Buchstabe der Zeile erlaubt. 
			Alles andere wird als Kommentar gedeutet. Wobei grundsätzlich das Doppelkreuz # als Kommentarindikator vorgesehehn ist.*/
			//ASCII-Nummer des ersten Zeichens des Strings.
			$ascii=ord($zeile);														
			if(($ascii>=65 && $ascii<=90) || ($ascii>=97 && $ascii<=122))	 	
			{
				//Zeile aufsplitten					
				$zerlegt = explode("=",$zeile);									
				/* Auszug aus dem PHP Manual zur Fkt. rtrim():
					Without the second parameter, rtrim() will strip these characters:
				    " " (ASCII 32 (0x20)), an ordinary space.
				    "\t" (ASCII 9 (0x09)), a tab.
				    "\n" (ASCII 10 (0x0A)), a new line (line feed).
				    "\r" (ASCII 13 (0x0D)), a carriage return.
				    "\0" (ASCII 0 (0x00)), the NULL-byte.
				    "\x0B" (ASCII 11 (0x0B)), a vertical tab.*/
				$trim = rtrim($zerlegt[1]);
				//Host-Einstellung				
				if($zerlegt[0]=="Host")
				{
					$host=$trim;
				}	
				//Datenbank-Einstellung				
				if($zerlegt[0]=="Datenbank")
				{
					$datenbank=$trim;
				}	
				//Nutzer-Einstellung				
				if($zerlegt[0]=="Nutzer")
				{
					$nutzer=$trim;
				}	
				//Passwort-Einstellung				
				if($zerlegt[0]=="Passwort")
				{
					$passwort=$trim;
				}	
				//Tabelle_Kaffee_Trinker-Einstellung				
				if($zerlegt[0]=="Tabelle_Kaffee_Trinker")
				{
					$tabelle_kaffee_trinker=$trim;
				}
				//Tabelle_Log-Einstellung				
				if($zerlegt[0]=="Tabelle_Log")
				{
					$tabelle_log=$trim;
				}//letztes if	
			}//Ignorieren von Kommentaren und Zeilen mit Leerzeichen am Anfang
		}//Ende der foreach-Schleife

		/*Dem Nutzer wird die aktuelle Konfiguration angezeigt.*/		
		echo "Folgende Konfiguration der Datenbank wurde vorgenommen:<br>";
		echo "Host:>$host<<br>";
		echo "Nutzer:>$nutzer<<br>";
		echo "Passwort:>$passwort<<br>";
		echo "Datenbank:>$datenbank<<br>";
		echo "Tabelle_Kaffee_Trinker:>$tabelle_kaffee_trinker<<br>";
		echo "Tabelle_Log:>$tabelle_log<<br>";
		
		/*Die Daten werden aus dem Formular in Variablen übernommen.*/
		$vorname = $_POST["vorname"];
		$nachname = $_POST["nachname"];
		$personalnummer = $_POST["personalnummer"];
		//Initialwert
		$kaffees = $_POST["kaffees"];		
		
		/*Die Verbindung zum Server wird aufgenommen.
		Sollte etwas schief gehen, wird eine Fehlermeldung ausgegeben.*/
		$verbindung = mysql_connect("$host","$nutzer","$passwort")or die ("Fehler: Es ist keine Verbindung m&oumlglich. &Uumlberprüfen Sie Benutzername und Passwort.<br>");
		 
		/*Die Datenbank wird ausgewählt. 
		Sollte etwas schief gehen, wird eine Fehlermeldung ausgegeben.*/
		mysql_select_db("$datenbank") or die ("Fehler: Die Datenbank existiert nicht.<br>");
		
		/*WICHTIG: Die While-Schleife muss später durch einen gewissen Zeitraum von z.B. 20 Minuten begrenzt werden über die Timestamps (einstellbar).*/		
		/*Die Tabelle_Log wird durchstöbert. Jede UID wird mit der Tabelle_Kaffee_Trinker abgeglichen.
		Dadurch soll ermittelt werden, welche UID noch nicht registriert ist und hinzugefügt werden soll.*/
		$abfrage_log = "SELECT uid FROM $tabelle_log";
		$ergebnis_log = mysql_query($abfrage_log);
		$m=0;
		while($zeile_log = mysql_fetch_object($ergebnis_log))
  		{
   		$uid_gefunden = $zeile_log->uid;
   		echo "Es wurde die UID $uid_gefunden gefunden.";
			$abfrage_kaffee_trinker = "SELECT * FROM $tabelle_kaffee_trinker WHERE uid = '$uid_gefunden'";
			$ergebnis_kaffee_trinker = mysql_query($abfrage_kaffee_trinker);
			$i=0;
			while($zeile_kaffee_trinker[$i] = mysql_fetch_object($ergebnis_kaffee_trinker))
	   	{
	   		$i++;
	   		echo "Das ist die $i te Zeile -> $zeile_kaffee_trinker[$i] <br>";
	   	}
	   	
	   	/*Werden mehr als nur eine Zeile gefunden, scheint es mehrere registrierte Benutzer auf eine Kaffee-Tasse zu geben! 
	   	Fehler! Das ist eklig!*/
	   	if(count($zeile_kaffee_trinker)>1)
	   	{
				echo "Fehler: Es scheint mehrere Benutzer einer Kaffeetasse mit der UID $uid_gefunden zu geben.<br>";  	
	   	}
	   	
	   	/*Es wurde ein Eintrag in der Log-Tabelle gefunden, der keinem bis jetzt bekannten Nutzer zugewiesen werden kann.*/
	   	if(count($zeile_kaffee_trinker)==1)
	   	{
				$uid[$m] = $uid_gefunden;
				echo "Es wurde die UID $uid in der Tabelle_Log gefunden.<br>"; 	
				$m++;
	   	}
	   }//Ende der Überprüfung der Log-Tabelle
	   
		/*Ein neuer Eintrag wird in die Tabelle_Kaffee_Trinker eingefügt.*/
		$eintrag = "INSERT INTO $tabelle_kaffee_trinker (uid, vorname, nachname, personalnummer, kaffees) VALUES ('$uid[0]', '$vorname', '$nachname', '$personalnummer', '$kaffees')";
		
		/*Der Befehl wird an den MySQL-Server übermittelt.*/
		$eintragen = mysql_query($eintrag);
		
		/*Wurde der Befehl vom Server umgesetzt?*/
		if($eintragen == true)
		{
		   echo "Der neue Kaffeetrinker $vorname $nachname wurde erfolgreich angelegt.<br>";
		}
		else
		{
		   echo "Fehler: Der Kaffeetrinker $vorname $nachname konnte nicht angelegt werden.<br>";
		}//Query erfolgreich?
	}//Wurde das Formular schon abgeschickt?

?>