# damstede-meldingen

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