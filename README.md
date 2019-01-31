# damstede-meldingen

![Een voorbeeld van een melding](https://github.com/FreekBes/damstede-meldingen/blob/master/imgs/melding.png "Een voorbeeld van een melding")

Met dit pakket van bestanden kun je meldingen op schermen bewerken en weergeven. Het pakket biedt ondersteuning aan meerdere schermen en meerdere locaties.

## Installatie

1. [Download deze repository](https://github.com/FreekBes/damstede-meldingen/archive/master.zip) en pak deze uit in een map op een webserver die PHP ondersteunt. Het pakket is geschreven in PHP versie 7.2.
2. Maak in de map `/import` een bestand aan genaamd `nogit.php`. Neem de volgende content hierin over maar vervang hierbij de strings met de benodigde waarden.

```php
<?PHP
	$salt = "plaats hier een salt om veilig te hashen";
	$hashedPassword = "plaats hier een wachtwoord gehashed met bovenstaande salt (gebruik de crypt-functie in PHP)";
	
	$zermeloSchool = "plaats hier het subdomein van de school op Zermelo (dus als het domein damstedelyceum.zportal.nl is, voer je hier pascalcollegedamstede in)";
	$zermeloUsername = "plaats hier een gebruikersnaam van Zermelo";
	$zermeloPassword = "plaats hier een wachtwoord van Zermelo";
?>
```

3. Creëer een map `content` en een map `screens` in de hoofdmap.
4. Maak één of meerdere schermen aan (zie onder).

### Het aanmaken van schermen

Om schermen aan te maken, maak je in de map waarin het pakket is uitgepakt het bestand `screens.json` aan. Hierin definiëer je de schermen die worden gebruikt. Neem een voorbeeld aan onderstaande code:

```json
{
	"docenten": {
		"id": "docenten",
		"name": "Docentenkamer",
		"location": "Hoofdgebouw",
		"file": "screens\/docenten.json",
		"last-usage": 0
	},
	"leerlingen": {
		"id": "leerlingen",
		"name": "Leerlingen",
		"location": "Hoofdgebouw",
		"file": "screens\/leerlingen.json",
		"last-usage": 0
	},
	"tweedelocatie": {
		"id": "tweedelocatie",
		"name": "Leerlingen",
		"location": "Tweede Locatie",
		"file": "screens\/tlleerlingen.json",
		"last-usage": 0
	}
}
```

Let op het feit dat het ID voor een scherm twee keer moet worden aangegeven, één keer als key en één keer als `id`. Deze twee waarden mogen niet van elkaar verschillen.

Voor elk scherm moet in de map `/screens` een bestand worden aangemaakt waar de `file`-key in `screens.json` naar verwijst. Elk van deze bestanden moet bestaan uit de volgende content:

```json
{
    "messages": []
}
```

Via het meldingenportaal worden deze bestanden automatisch aangevuld met berichten die later op de schermen zullen verschijnen.

## Het beheren van meldingen

Om meldingen te beheren, ga je naar de website waar je het pakket hebt uitgepakt. Hier krijg je twee opties, om meldingen weer te geven en om meldingen te beheren. Selecteer de tweede. Log vervolgens in met het eerder aangemaakte wachtwoord. Je komt nu op een "portaal" uit waar meldingen kunnen worden beheerd.

![Meldingenportaal screenshot](https://github.com/FreekBes/damstede-meldingen/blob/master/imgs/portal.png "Het meldingenportaal")

## Het weergeven van meldingen op TV-schermen

Om meldingen weer te geven op TV-schermen, heb je verschillende opties. Je kunt voor elk scherm een aparte computer neerzetten die de meldingen voor dat scherm weergeeft. Wat echter een goedkopere optie is, is om voor elk scherm een Chromecast aan te schaffen, en vanaf één computer naar alle chromecasts tegelijk casten. Dit is niet zomaar mogelijk (Chrome limiteert het casten tot maximaal 1 apparaat). Er is echter wel een makkelijke omweg beschikbaar die het wel mogelijk maakt. Uitleg en instructies hiervoor kun je [hier](https://troypoint.com/chromecast-to-multiple-devices/) vinden.

Een andere mogelijkheid is om te werken met Raspberry Pi's, maar hier is wel ervaring voor nodig. Ook kun je werken met Apple TV's, mocht je een Apple-fanaat zijn. Eigenlijk worden alle apparaten die websites kunnen weergeven ondersteund. Je zou zelfs, bij wijze van spreken, voor een klein prijsje een oude Nintendo Wii op de kop kunnen tikken en deze ervoor gebruiken (in theorie, dit is niet getest en ik beveel het ook niet echt aan eigenlijk).

Voor het weergeven van meldingen ga je naar de website waar je het pakket hebt uitgepakt, selecteer "Weergeef meldingen", kies het gewenste scherm en klaar!

## Gebruikte onderdelen

Voor dit pakket zijn de volgende onderdelen gebruikt:
* [jQuery](https://jquery.com/)
* [nProgress](http://ricostacruz.com/nprogress/)
* [ZermeloRoosterPHP door wvanbreukelen](https://github.com/wvanbreukelen/ZermeloRoosterPHP)

Omdat de versie die op het Damstede Lyceum gebruikt wordt, wordt gehost door [DigitalOcean](https://www.digitalocean.com), staat er in het beheerdersportaal een link naar deze host toe. Deze kun je natuurlijk weghalen of vervangen met jouw eigen host.

Ook staan er nog drie knoppen; eentje verwijst naar [shellinabox](https://help.ubuntu.com/community/shellinabox) een tweede verwijst naar [Webmin](http://www.webmin.com). Deze programma's kun je installeren mocht je een Linux-distributie gebruiken, maar dit is niet vereist. De laatste knop brengt je naar deze GitHub-repository.

## Foutoplossing

Hier staan een aantal veel voorkomende fouten met oplossingen.

### Ik gebruik Mozilla Firefox en het beheerdersportaal werkt niet goed

Het meldingsportaal is geoptimaliseerd voor gebruik in Webkit-browsers. Dit betekent dat het portaal optimaal werkt in Google Chrome, Opera en Microsoft Edge. Het wordt dan ook aangeraden om één van deze browsers te gebruiken.

### Er ging iets mis!-melding

Als er constant een Er ging iets mis!-melding in beeld komt bij het weergeven van meldingen, zit er ergens een fout in de PHP-code. Controleer of je gebruik maakt van de juiste PHP-versie (7.2). Indien je verstand hebt van PHP-code kun je natuurlijk ook de code zelf debuggen, door bovenin elk PHP-bestand `error_reporting(E_ALL); ini_set('display_errors', 1);` te schrijven.

### Wachtwoord is constant onjuist

Als het wachtwoord constant onjuist is, is het misschien het handigst om deze te resetten. Doe dit als volgt: maak een PHP-bestand aan met hierin:

```php
$salt = 'plaats jouw salt hier';
echo crypt('plaats het nieuwe wachtwoord hier', $salt);
```

Voer deze code uit in een terminal of webbrowser. De uitkomst is het nieuwe wachtwoord, die als `$password` moet worden ingesteld in `nogit.php` (map `import`).

Wat uitleg: de salt wordt gebruikt als extra beveiliging voor het hashen van het wachtwoord. Dit mag van alles zijn.

### Meldingen kunnen niet worden toegevoegd, gewijzigd of verwijderd via het portal

Zorg ervoor dat de mappen `screens` en `content` lees- en schrijfbaar zijn door PHP. Dit verschilt per operating system. In Linux kun je hiervoor het commando `chmod` gebruiken: `$ chmod -R 0766 /screens` en `$ chmod -R 0766 /content`.

### Ik kan geen start- en einddatum wijzigen voor videomeldingen

Bij videomeldingen staat momenteel geen knop om een melding te bewerken. Dit betekent dat start- en einddatums voor videomeldingen tevens niet bewerkbaar zijn. Omdat videomeldingen zo weinig worden gebruikt, is er nog niet gekeken naar een oplossing hiervoor. Als je alsnog deze twee waardes aan moet passen, verwijder de melding dan en voeg deze opnieuw toe met de juiste data (denk er om dat dit even kan duren bij videomeldingen i.v.m. de bestandsgrootte van video).

### Er gebeurt niets als ik op de edit- of verwijderknop druk bij een melding

Dit gebeurt nog wel eens wanneer er gebruik is gemaakt van enkele of dubbele aanhalingstekens. Als dit gebeurt, [rapporteer dit dan als issue](https://github.com/FreekBes/damstede-meldingen/issues/new) in deze repository, zodat dit zo snel mogelijk kan worden opgelost.

### De Zermelo-integratie werkt niet

De Zermelo-integratie is nogal instabiel. Zorg ervoor dat de gegevens in `nogit.php` (map `import`) kloppen. Als dit al het geval is, controleer dan of je met de gegevens wel kunt inloggen op Zermelo Portal zelf.

### Mijn video wordt na 5 minuten afgekapt

Na 5 minuten worden alle meldingen automatisch gerefreshed, uit voorzorg. Dit is om stilstaande beelden te voorkomen bij errors in het programma. Om deze reden is het verstandig geen video's die langer dan 5 minuten duren te gebruiken als melding (überhaupt is dat misschien niet zo handig).
