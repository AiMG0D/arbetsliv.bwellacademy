# Bewell app
Projektet är byggt i Laravel version 9.

## Krav
* PHP 8.0
* [Composer](https://getcomposer.org/)

## Installation

Installera beroenden med composer
```
composer install
```

Du kan använda en `.env` fil för att sätta miljövaribler. Kopiera `.env.example` till `.env` i rotkatalogen.

Skapa sedan en nyckel för kryptering av sessioner och annan data
```
php artisan key:generate
```

Lägg till följande variabler
* `FMS_TYPE` ska vara `school` för skola eller `work` för arbetsliv versionen.
* `FMS_MOCK_PROFILE` är ID på en profil som visas som ett exempel i appen.

Starta utvecklingsserver
```
php artisan serve
```

## Katalogstruktur
Projektet använder den katalogstruktur som är standard för Laravel.

För att enkelt se vilken controller och views som används på en sida så kan du kolla i `laravel-debugbar`. Aktivera den genom att sätta `APP_DEBUG=true` i `.env` filen.

### Katalogen `app`
Här finns models.

* `Country.php` - Land.
* `County.php` - Län.
* `ElementType.php` - Typ av fråga.
* `GroupType.php` - Typ av frågegrupp.
* `Profile.php` - Profil.
* `ProfileFactor.php` - Faktor.
* `ProfileText.php` - Svarstext i livsstilsplanen.
* `ProfileValue.php` - Värdet som eleven valt eller skrivit in.
* `QuestionnaireCategory.php` - Kategori/faktor.
* `QuestionnaireGroup.php` - Frågegrupp.
* `QuestionnairePage.php` - Sida i enkäten.
* `QuestionnaireQuestion.php` - Fråga i enkäten.
* `Role.php` - Behörighet. T.ex admin eller elev.
* `RoleUser.php` - Behörigheter kopplade till en användare.
* `SampleGroup.php` - Urvalsgrupp.
* `SampleGroupMember.php` - Medlemmar i en urvalsgrupp.
* `SecondaryProgram.php` - Gymnasieprogram.
* `Section.php` - Klass eller avdelning (företag).
* `StatsFilter.php` - Sparade filter i statistiken.
* `Tile.php` - Puff på startsida.
* `Unit.php` - Skola eller företag.
* `UnitStaff.php` - Personal på en skola eller företag.
* `User.php` - Användare.
* `UserInfo.php` - Telefonnummer och andra användaruppgifter.

Följande filer används inte längre
* `Administrator.php`
* `Customer.php`
* `Group.php`
* `UserLogin.php`

### Katalogen `app/Console/Commands`
Några kommandon för att uppdatera användare och statistik.

Om du vill uppdatera alla profiler efter ändringar i koden så kan du köra
```
php artisan stats:refresh
```

### Katalogen `app/Http`
* `helpers.php` - Hjälpfunktioner som är tillgängliga globalt.

### Katalogen `app/Http/Controllers`
Här finns controllers som hanterar alla anrop till sidan.

* `HomeController.php` - Startsidan.
* `LocalizationController.php` - Val av språk.
* `LoginController.php` - Inloggning.
* `ProfileController.php` - Profiler/livsstilsanalys.
* `QuestionnaireGroupsController.php` - Frågegrupper. Här redigerar man frågorna.
* `QuestionnairePageController.php` - Sidor för enkäten.
* `RegisterController.php` - Registrering.
* `RemindersController.php` - Återställning av lösenord.
* `SamplesController.php` - Urvalsgrupper.
* `SectionController.php` - Klasser eller avdelningar (företag).
* `StaffController.php` - Personal.
* `StatementController.php` - Livsstilsplan och jämför profiler.
* `StatsController.php` - Statistik.
* `StudentController.php` - Elever eller anställda (företag).
* `UnitController.php` - Skola eller företag.
* `UserInfoController.php` - Visa information om användare.

### Katalogen `app/Nobox/Calculation`
Uträkning av värden och faktorer.

### Katalogen `database/migrations`
Filer för uppdatering av databasen.

Uppdatera med
```
php artisan db:migrate
```

### Katalogen `public`
Statiska filer som är allmänt tillgängliga.

* `css` - CSS.
* `etc` - Manualer och exempelfiler för import.
* `fonts` - Typsnitt.
* `images` - Bilder.
* `js` - JavaScript.
* `vendor` - Externa JavaScript bibliotek.

### Katalogen `resources/views`
Mallar för alla sidor.

### Katalogen `resources/lang`
Filer för översättning.

### Katalogen `routes`
* `web.php` - Routes för alla sidor.

### Katalogen `storage/logs`
Loggfiler.

mysqldump -u root -p bwellbackend > /home/vito/bwell/backend/21_3_2025.sql
