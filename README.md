# damstede-meldingen

![Een voorbeeld van een melding](https://github.com/FreekBes/damstede-meldingen/blob/master/imgs/melding.png "Een voorbeeld van een melding")

Met dit pakket van bestanden kun je meldingen op schermen bewerken en weergeven. Het pakket biedt ondersteuning aan meerdere schermen en meerdere locaties.

## Installatie

1. Download deze repository en plaats deze in een map op een webserver die PHP ondersteunt. Het pakket is geschreven in PHP versie 7.2.
2. Maak in de map `/import` een bestand aan genaamd `nogit.php`. Neem de volgende content hierin over maar vervang hierbij de strings met de benodigde waarden.

```php
<?PHP
	$salt = "plaats hier een salt om veilig te hashen";
	$hashedPassword = "plaats hier een wachtwoord gehashed met bovenstaande salt (gebruik de crypt-functie in PHP)";
	
	$zermeloSchool = "plaats hier het subdomein van de school op Zermelo (dus als het domein pascalcollegedamstede.zportal.nl is, voer je hier pascalcollegedamstede in)";
	$zermeloUsername = "plaats hier een gebruikersnaam van Zermelo";
	$zermeloPassword = "plaats hier een wachtwoord van Zermelo";
?>
```

3. Maak één of meerdere schermen aan (zie onder).

### Het aanmaken van schermen

Om schermen aan te maken, maak je in de map waarin het pakket is uitgepakt het bestand `screens.json` aan. Hierin definiëer je de schermen die worden gebruikt. Neem een voorbeeld aan onderstaande code:

```json
{
	"docenten": {
		"id": "docenten",
		"name": "Docentenkamer",
		"location": "Hoofdgebouw",
		"file": "screens/docenten.json",
		"last-usage": 0
	},
	"leerlingen": {
		"id": "leerlingen",
		"name": "Leerlingen",
		"location": "Hoofdgebouw",
		"file": "screens/leerlingen.json",
		"last-usage": 0
	},
	"tweedelocatie": {
		"id": "tweedelocatie",
		"name": "Leerlingen",
		"location": "Tweede Locatie",
		"file": "screens/tlleerlingen.json",
		"last-usage": 0
	}
}
```

Voor elk scherm moet in de map `/screens` een bestand worden aangemaakt waar de `file`-key in `screens.json` naar verwijst. Deze moet bestaan uit de volgende content:

```json
{
    "messages": []
}
```

## Het beheren van meldingen

Om meldingen te beheren, ga je naar de website waar je het pakket hebt uitgepakt. Hier krijg je twee opties, om meldingen weer te geven en om meldingen te beheren. Selecteer de tweede. Log vervolgens in met het eerder aangemaakte wachtwoord. Je komt nu op een "portaal" uit waar meldingen kunnen worden beheerd.

![Meldingenportaal screenshot](https://github.com/FreekBes/damstede-meldingen/blob/master/imgs/portal.png "Het meldingenportaal")

## Gebruikte onderdelen

Voor dit pakket zijn de volgende onderdelen gebruikt:
* [jQuery](https://jquery.com/)
* [nProgress](http://ricostacruz.com/nprogress/)
* [ZermeloRoosterPHP door wvanbreukelen](https://github.com/wvanbreukelen/ZermeloRoosterPHP)

Omdat de versie die op Damstede gebruikt wordt, wordt gehost door [DigitalOcean](https://www.digitalocean.com), staat er in het beheerdersportaal een link naar deze host toe. Deze kun je natuurlijk weghalen of vervangen met jouw eigen host.

Ook staan er nog twee knoppen; eentje verwijst naar [shellinabox](https://help.ubuntu.com/community/shellinabox) en de andere verwijst naar [Webmin](http://www.webmin.com). Deze programma's kun je installeren mocht je een Linux-distributie gebruiken, maar dit is niet vereist.