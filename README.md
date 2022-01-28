# f.technology WP Helpers
[GitHub](https://github.com/F-technology-srl/ftechnology-wphelpers.git) repository

## Requirements

Assicurati che tutte le dipendenze siano state installate prima di procedere:

* [WordPress](https://wordpress.org/) >= 4.7
* [PHP](https://secure.php.net/manual/en/install.php) >= 7.1.3 (with [`php-mbstring`](https://secure.php.net/manual/en/book.mbstring.php) enabled)
* [Composer](https://getcomposer.org/download/)

## Helpers installation

Aggiungi al composer.json del tuo tema le seguenti righe.

```json
  "require": {
    ..
    ..
    "ftechnology/wphelpers": "dev-master"
  },
  "repositories":[
    {
      "type": "vcs",
      "url": "git@github.com:F-technology-srl/ftechnology-wphelpers.git"
    }
  ]
```

quindi puoi aggiornare le dipendenze 

```shell
# @ app/themes/ or wp-content/themes/
$ composer update
```

Adesso il tuo ambiente è pronto per utilizzare gli helpers :punch:

## Helpers usage

### WP Helper
Funzioni utilizzabili sull'inizializzazione.  

Se stai usando sage inizializa l'helper in `app/setup.php`, altrimenti puoi farlo nel file `function.php` del tuo tema.  
ATTENZIONE: l'helpers `Ftechnology\WPHelpers\WP` interagisce ed utilizza diverse funzioni native di `WordPress`, quindi è necessario che queste siano caricate prima di utilizzare l'helper stesso.  
Per questo motivo utilizza il seguente `hook` prima di creare una nuova istanza dell'helper.

```php
use Ftechnology\WPHelpers\WP as ftechnologyWpHelpers;

add_action('after_setup_theme', function () {
    
    // Creo un'istanza
    $ftechnologWpHelpers = new ftechnologyWpHelpers();
    
    /**
    * Personalizza la schermata di login di WordPress
    * È necessario passare come argomenti il logo e le sue dimensioni
    */
    $ftechnologWpHelpers->stylizeAdminAddAction(asset_path('images/logo.png'),320,34);
    
    /**
    * Rimuove le voci di menù nel wp-admin
    * I primo array rimuove le voci per tutti
    * Il secondo solo per i NON admin
    */
    $ftechnologWpHelpers->removeAdminMenuPage(['edit-comments.php'],['edit.php?post_type=acf-field-group','users.php','themes.php','plugins.php','tools.php','options-general.php','upload.php']);
    
    /**
    * Rimuove le voci nell'admin bar
    * I primo array rimuove le voci per tutti
    * Il secondo solo per i NON admin
    */
    $ftechnologWpHelpers->removeAdminMenuBar([],['new-post','new-page','new-media','comments','wpseo-menu']);

    /**
    * Disabilita le entità single per i tipi passati
    * Oltre a rimuovere la generazione del template single vengono anche rimosse le voci di anteprima del wp-admin
    */
    $ftechnologWpHelpers->disableSingle(['ca_guide']);

    /**
    * Disabilita l'editors per i post type e i template specificati
    */
    $ftechnologWpHelpers->removeEditor([],['views/template-custom.blade.php'],[2]);
   
});
```

Utilizzo nei templates  

La segunete funzione ritorna le veci di un nome_menu in aray di oggetti, utile per ciclare le voci di un menù.
```php
$preHeaderMenuTree = \Ftechnology\WPHelpers\WP::wpMenuToTree('pre-header-menu');
```

Se in un progetto [Sage](https://roots.io/sage/) c'è la necessità di recuperare l'`output html` di un `blade.php`
```php
$html = self::loadBladePartContent('views/partials/magazine.blade.php');
```
.. per i progetti `NON` [Sage](https://roots.io/sage/) utilizza invece 
```php
$html = self::loadTemplatePartContent('template.php');
```

Per ordinare un array di post WordPress 
```php
self::orderArrayPostBy($posts, $orderBy, $order = 'ASC', $unique = true);
```

Per recuperare l'alt tag di un immagine con fallback sul titolo articolo 
```php
self::getAltTag($forceTitle = false, $id = 0);
```

### Utils Helper usage
Nell'`Utils` sono presenti funzioni di utilità come debug, gestione delle date, array sorting ecc.

```php
use \Ftechnology\WPHelpers\Utils;

Utils::debug(
    [
        'say' => 'Hello word',
        'time' => time(),
    ]
);

$obj = Utils::vec2obj(
    [
        ['id' => 1, 'count' => 34, 'name' => 'Leo'],
        ['id' => 2, 'count' => 156, 'name' => 'Teo'],
        ['id' => 3, 'count' => 99, 'name' => 'Peo']
    ]
);

// Altre utility disponibili

Utils::debug(array(

    // Converte secondi in Duration ISO8601, es. 3600 secondi = PT1H
    'secondsToDurationISO8601' => Utils::secondsToDurationISO8601(3600),
    
    // Converte una Duration ISO8601 in un oggetto years/months/days/hours/minutes
    'parseDuration' => Utils::parseDuration('P1Y2M10DT2H30M'),
    
    // Ritorna la differenza in years/months/days/hours/minutes/seconds/milliseconds tra un intervallo di date 
    'getTotalInterval' => Utils::getTotalInterval('hours', '2019-05-22 22:00', '2019-05-22 10:35'),

    // Validatore di data in base ad un formato
    'validateDate1' => Utils::validateDate('16-03-2019', 'Y-m-d') ? 'true' : 'false',
    'validateDate2' => Utils::validateDate('16-03-2019', 'd-m-Y') ? 'true' : 'false',
    
    // Converte una data da un formato ad un altro (se il formato iniziale non corrisponde alla data passata ritorna FALSE)
    'convertDateFormat1' => Utils::convertDateFormat('16-13-2019', $currentFormat = 'd-m-Y', $newFormat = 'Y-m-d'),
    'convertDateFormat2' => Utils::convertDateFormat('16-13-2019', $currentFormat = 'd/m/Y', $newFormat = 'Y-m-d'),
    
    'customWordCutString' => Utils::customWordCutString('testo di prova', $start = 0, $words = 15, $suffix = '...')
));



$var1 = Utils::customWordCutString($str, $start = 0, $words = 15, $suffix = '...');
$var2 = Utils::getArrayPortion($file, $start = false, $end = false);

Utils::sendMailLog($html, $subject, $mailTo = false, $mailFromName = '', $mailFrom = '');

```

enjoy :sunglasses:
