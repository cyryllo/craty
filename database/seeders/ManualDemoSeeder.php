<?php

namespace Database\Seeders;

use App\Models\AppSetting;
use App\Models\Category;
use App\Models\Consumable;
use App\Models\Contact;
use App\Models\Item;
use App\Models\ItemHistory;
use App\Models\Loan;
use App\Models\LoanGroup;
use App\Models\MachineBooking;
use App\Models\Repair;
use App\Models\Reservation;
use App\Models\SaleListing;
use App\Models\Stocktake;
use App\Models\StorageLocation;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\InventoryNumberGenerator;
use App\Services\QrCodeGenerator;
use App\Support\LocationScope;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Pełny zestaw przykładów do instrukcji użytkownika (docs/instrukcja/) i jej
 * zrzutów ekranu: kontakty, wypożyczenia (w terminie, po terminie, zwrócone),
 * zestaw z częściowym zwrotem, rezerwacje, naprawy, przeglądy, gwarancje,
 * materiały, inwentaryzacje i sprzedaż. Tylko dla środowiska deweloperskiego,
 * instalator dalej wgrywa sam DemoDataSeeder:
 *
 *   ./art migrate:fresh --seed --seeder=ManualDemoSeeder --force
 *
 * Daty są rozłożone w czasie (Carbon::setTestNow), żeby historia, terminy i
 * ostrzeżenia wyglądały jak po kilku tygodniach pracy. Zdjęcia: jeśli w
 * app-storage/app/manual-photos/ leży plik nazwany jak przedmiot (slug nazwy,
 * .jpg/.png), trafia jako zdjęcie główne; bez nich przedmioty są bez zdjęć.
 */
class ManualDemoSeeder extends Seeder
{
    private User $admin;

    private User $worker;

    private int $tick = 0;

    public function run(): void
    {
        $this->call(DatabaseSeeder::class);

        $this->admin = User::where('email', 'admin@craty.test')->firstOrFail();
        $this->worker = User::where('email', 'magazynier@craty.test')->firstOrFail();
        $this->admin->forceFill(['name' => 'Ewa Kowalska'])->save();
        $this->worker->forceFill(['name' => 'Tomek Wiśniewski'])->save();
        auth()->setUser($this->admin);
        // Etykiety zapisywane w bazie (np. miejsce inwentaryzacji) powstają w
        // języku osoby, która je tworzy; w konsoli domyślnie byłby angielski.
        app()->setLocale('pl');

        AppSetting::current()->fill([
            'name' => 'Pracownia Kowalskich',
            'locale' => 'pl',
            'module_service_enabled' => true,
            'module_consumables_enabled' => true,
            'module_bookings_enabled' => true,
            'service_reminders_enabled' => true,
            'loan_due_notifications_enabled' => true,
            'public_contact_email' => 'pracownia@example.com',
            'public_contact_phone' => '600 100 200',
        ])->save();

        $today = Carbon::today();

        // ---------- struktura ----------
        $main = Warehouse::where('code', 'M1')->firstOrFail();
        $hallShelf = StorageLocation::where(['rack' => '3', 'shelf' => '2'])->whereNull('bin')->firstOrFail();
        $hallBin = StorageLocation::where(['rack' => '3', 'shelf' => '2', 'bin' => '1'])->firstOrFail();
        $toolsRack = StorageLocation::where('rack', '7')->whereNull('shelf')->firstOrFail();
        $toolsShelf = StorageLocation::where('rack', '7')->where('shelf', '1')->firstOrFail();

        $garage = Warehouse::create(['name' => 'Garaż', 'code' => 'G1', 'address' => 'ul. Polna 12']);
        StorageLocation::create(['warehouse_id' => $garage->id]);
        $garageRack = StorageLocation::create(['warehouse_id' => $garage->id, 'rack' => '1']);
        $garageShelf = StorageLocation::create(['warehouse_id' => $garage->id, 'rack' => '1', 'shelf' => '2']);

        $cat = fn (string $code) => Category::where('code', $code)->firstOrFail();
        $measure = Category::create(['name' => 'Narzędzia pomiarowe', 'code' => 'POM', 'parent_id' => $cat('NAR')->id]);
        $ladders = Category::create(['name' => 'Drabiny i rusztowania', 'code' => 'DRA']);
        $av = Category::create(['name' => 'Sprzęt AV', 'code' => 'AV']);

        // ---------- kontakty ----------
        $jan = Contact::create(['name' => 'Jan Nowak', 'type' => 'osoba', 'phone' => '501 234 567', 'email' => 'jan.nowak@example.com', 'notes' => 'Sąsiad, pożycza głównie elektronarzędzia.']);
        $anna = Contact::create(['name' => 'Anna Zielińska', 'type' => 'osoba', 'phone' => '602 345 678', 'email' => 'anna.zielinska@example.com']);
        $budmex = Contact::create(['name' => 'Budmex Sp. z o.o.', 'type' => 'firma', 'phone' => '12 345 67 89', 'email' => 'biuro@budmex.example.com', 'address' => "ul. Budowlana 5\n30-001 Kraków", 'notes' => 'Ekipa remontowa, sprzęt odbiera brygadzista Marek.']);
        $club = Contact::create(['name' => 'Koło robotyki, Technikum nr 2', 'type' => 'firma', 'email' => 'robotyka@example.com', 'notes' => 'Opiekun: p. Lewandowski.']);
        $service = Contact::create(['name' => 'Serwis Elektronarzędzi Kraków', 'type' => 'serwis', 'phone' => '12 222 33 44', 'email' => 'serwis@example.com', 'address' => "ul. Naprawcza 3\n31-002 Kraków"]);

        // ---------- przedmioty ----------
        $this->at($today->copy()->subDays(45));
        $item = fn (string $name) => Item::where('name', $name)->firstOrFail();

        $drill = $item('Wiertarka udarowa Bosch GSB 13 RE');
        $grinder = $item('Szlifierka kątowa Makita 9557');
        $bits = $item('Zestaw wierteł do metalu 1-13mm');
        $profiles = $item('Profile aluminiowe 20x20 (6 szt.)');
        $meter = $item('Multimetr cyfrowy UNI-T UT61E');
        $drill->update(['purchased_at' => $today->copy()->subYears(3), 'warranty_until' => $today->copy()->subYear()]);

        $new = fn (array $data, ?StorageLocation $location) => $this->item($data, $location);

        $driver = $new(['name' => 'Wkrętarka akumulatorowa Makita DDF482', 'category_id' => $cat('ELN')->id, 'value' => 450, 'condition' => 'nowy', 'serial_number' => 'DDF482-7731902', 'ean' => '0088381640358', 'description' => '18 V, 2 akumulatory 3 Ah, ładowarka, walizka Makpac.', 'purchased_at' => $today->copy()->subMonths(10), 'warranty_until' => $today->copy()->addDays(21)], $hallShelf);
        $saw = $new(['name' => 'Pilarka tarczowa DeWalt DWE560', 'category_id' => $cat('ELN')->id, 'value' => 520, 'condition' => 'uzywany', 'serial_number' => 'DWE560-2041188', 'inspection_interval_months' => 12, 'description' => 'Tarcza 184 mm, prowadnica równoległa w komplecie.'], $toolsShelf);
        $vacuum = $new(['name' => 'Odkurzacz przemysłowy Kärcher WD 5', 'category_id' => $cat('ELN')->id, 'value' => 690, 'condition' => 'uzywany', 'serial_number' => 'WD5-010932'], $toolsRack);
        $laser = $new(['name' => 'Laser krzyżowy Bosch GLL 3-80', 'category_id' => $measure->id, 'value' => 1250, 'condition' => 'uzywany', 'serial_number' => '601063S00-552', 'description' => 'Statyw, uchwyt magnetyczny, walizka L-Boxx.'], $toolsShelf);
        $rangefinder = $new(['name' => 'Dalmierz laserowy Bosch GLM 50', 'category_id' => $measure->id, 'value' => 380, 'condition' => 'uzywany'], $toolsShelf);
        $reel = $new(['name' => 'Przedłużacz bębnowy 25 m', 'category_id' => $cat('NAR')->id, 'value' => 160, 'condition' => 'uzywany'], $toolsRack);
        $ladder = $new(['name' => 'Drabina aluminiowa 3x9', 'category_id' => $ladders->id, 'value' => 740, 'condition' => 'uzywany', 'inspection_interval_months' => 12, 'description' => 'Trzyczęściowa, maks. wysokość robocza 6,2 m.'], $garageRack);
        $projector = $new(['name' => 'Projektor Epson EB-W06', 'category_id' => $av->id, 'value' => 1900, 'condition' => 'uzywany', 'serial_number' => 'X8L7210993', 'description' => 'Pilot, kabel HDMI 5 m, torba.'], $garageShelf);
        $speaker = $new(['name' => 'Głośnik JBL PartyBox 110', 'category_id' => $av->id, 'value' => 1400, 'condition' => 'uzywany'], $garageShelf);
        $iron = $new(['name' => 'Stacja lutownicza Hakko FX-888D', 'category_id' => $cat('ELE')->id, 'value' => 780, 'condition' => 'uzywany', 'serial_number' => 'FX888D-23BY'], $hallBin);
        $scope = $new(['name' => 'Oscyloskop Rigol DS1054Z', 'category_id' => $cat('ELE')->id, 'value' => 1700, 'condition' => 'uzywany', 'serial_number' => 'DS1ZA2041'], $hallShelf);
        $oldDrill = $new(['name' => 'Wiertarka Celma WSP-13', 'category_id' => $cat('ELN')->id, 'value' => 40, 'condition' => 'uszkodzony', 'description' => 'Uszkodzony wyłącznik, do utylizacji.'], $toolsRack);
        $oldDrill->update(['status' => 'wycofany']);

        // ---------- przeglądy i gwarancje ----------
        $this->inspect($ladder, $today->copy()->subYear()->subDays(6), 'Stopki i zawiasy bez uwag.');
        $this->inspect($saw, $today->copy()->subYear()->addDays(9), 'Osłona tarczy działa, kabel bez przetarć.');

        // ---------- historia: zwrócone wypożyczenie ----------
        $this->at($today->copy()->subDays(24));
        $loan = Loan::lend($drill, $jan, $jan->name, $this->admin, ['due_at' => $today->copy()->subDays(17), 'notes' => 'Z walizką i dwoma wiertłami.']);
        $this->at($today->copy()->subDays(18));
        $loan->registerReturn($this->worker, 'uzywany', 'Brak jednego wiertła 8 mm.');

        // ---------- naprawa zakończona ----------
        $this->at($today->copy()->subDays(30));
        $done = $this->sendForRepair($grinder, $service, 'Iskrzy przy uruchamianiu, traci moc.', $today->copy()->subDays(30), $today->copy()->subDays(16));
        $this->at($today->copy()->subDays(15));
        $this->completeRepair($done, $today->copy()->subDays(15), 120, 'Wymiana szczotek węglowych i przełącznika.');

        // ---------- naprawa w toku, po terminie ----------
        $this->at($today->copy()->subDays(12));
        $this->sendForRepair($vacuum, $service, 'Słabe ssanie, głośna praca silnika.', $today->copy()->subDays(12), $today->copy()->subDays(2));

        // ---------- wypożyczenia w toku ----------
        $this->at($today->copy()->subDays(16));
        Loan::lend($iron, $club, $club->name, $this->worker, ['due_at' => $today->copy()->subDays(3), 'notes' => 'Na zajęcia z lutowania, z zapasowym grotem.']);
        $this->at($today->copy()->subDays(2));
        Loan::lend($scope, $anna, $anna->name, $this->admin, ['due_at' => $today->copy()->addDays(5)]);

        // ---------- zestaw z częściowym zwrotem ----------
        $this->at($today->copy()->subDays(6));
        $kit = LoanGroup::create([
            'contact_id' => $budmex->id, 'borrower_name' => $budmex->name, 'due_at' => $today->copy()->addDays(4),
            'notes' => 'Wykończenie mieszkania przy ul. Lipowej.', 'issued_by' => $this->admin->id, 'borrowed_at' => now(),
        ]);
        foreach ([$laser, $rangefinder, $reel] as $kitItem) {
            Loan::lend($kitItem, $budmex, $budmex->name, $this->admin, ['due_at' => $kit->due_at, 'notes' => $kit->notes, 'loan_group_id' => $kit->id]);
        }
        $this->at($today->copy()->subDays(1));
        $kit->loans()->where('item_id', $reel->id)->firstOrFail()->registerReturn($this->worker, null, null);

        // ---------- rezerwacje ----------
        $this->at($today->copy()->subDays(3));
        Reservation::create(['item_id' => $speaker->id, 'contact_id' => $jan->id, 'borrower_name' => $jan->name, 'starts_on' => $today, 'ends_on' => $today->copy()->addDays(2), 'notes' => 'Urodziny w ogrodzie.', 'created_by' => $this->admin->id]);
        Reservation::create(['item_id' => $projector->id, 'contact_id' => $club->id, 'borrower_name' => $club->name, 'starts_on' => $today->copy()->addDays(8), 'ends_on' => $today->copy()->addDays(9), 'notes' => 'Prezentacja projektów na dniu otwartym.', 'created_by' => $this->worker->id]);
        Reservation::create(['item_id' => $laser->id, 'contact_id' => $anna->id, 'borrower_name' => $anna->name, 'starts_on' => $today->copy()->addDays(12), 'ends_on' => $today->copy()->addDays(14), 'created_by' => $this->admin->id]);

        // ---------- szybkie dodanie ze skanera ----------
        $this->at($today->copy()->subDays(1));
        $quick = $this->item(['name' => 'Zasilacz laboratoryjny', 'ean' => '5901234123457', 'condition' => 'uzywany'], null);
        $quick->forceFill(['needs_completion' => true])->saveQuietly();

        // ---------- sprzedaż ----------
        $this->at($today->copy()->subDays(9));
        SaleListing::create(['item_id' => $meter->id, 'title' => 'Multimetr UNI-T UT61E, True RMS, z przewodami', 'description' => "Sprawny, używany w domowym warsztacie.\nW zestawie przewody pomiarowe i kabel USB.", 'price' => 150, 'status' => 'wyeksportowana', 'exported_at' => now()]);
        SaleListing::create(['item_id' => $profiles->id, 'title' => 'Profile aluminiowe 20x20, 6 sztuk po 1 m', 'description' => 'Nowe, nieużywane, z nadwyżki po projekcie.', 'price' => 70, 'status' => 'szkic']);

        // ---------- materiały eksploatacyjne ----------
        $this->at($today->copy()->subDays(40));
        $consumables = [
            ['name' => 'Wkręty do drewna 4x40', 'unit' => 'opak', 'min_quantity' => 5, 'storage_location_id' => $hallBin->id, 'notes' => 'Opakowanie 200 szt.', 'moves' => [10, -3, -2, -2]],
            ['name' => 'Taśma izolacyjna czarna', 'unit' => 'szt', 'min_quantity' => 5, 'storage_location_id' => $hallBin->id, 'moves' => [20, -4, -3]],
            ['name' => 'Przewód YDY 3x1,5', 'unit' => 'm', 'min_quantity' => 20, 'storage_location_id' => $toolsRack->id, 'moves' => [100, -25, -12.5, -25]],
            ['name' => 'Klej montażowy Soudal', 'unit' => 'szt', 'min_quantity' => 2, 'storage_location_id' => $toolsShelf->id, 'moves' => [4, -2, -1]],
            ['name' => 'Opaski zaciskowe 200 mm', 'unit' => 'opak', 'min_quantity' => 2, 'storage_location_id' => $hallBin->id, 'moves' => [5, -1]],
            ['name' => 'Tarcze do cięcia metalu 125 mm', 'unit' => 'szt', 'min_quantity' => 10, 'storage_location_id' => $hallShelf->id, 'moves' => [25, -6, -5]],
        ];
        foreach ($consumables as $i => $data) {
            $moves = $data['moves'];
            unset($data['moves']);
            $consumable = Consumable::create($data + ['category_id' => $cat('MAT')->id]);
            foreach ($moves as $n => $change) {
                $this->at($today->copy()->subDays(40 - $n * 9 - $i));
                $consumable->adjust($change, $n === 0 ? 'Zakup w hurtowni' : null, $n % 2 ? $this->worker : $this->admin);
            }
        }

        // ---------- maszyny stacjonarne i rezerwacje czasu ----------
        $this->at($today->copy()->subDays(20));
        $machinesCategory = Category::create(['name' => 'Maszyny', 'code' => 'MAS']);
        $hallRoom = StorageLocation::where('room_id', $hallShelf->room_id)->whereNull('rack')->firstOrFail();
        $printer = $this->item(['name' => 'Drukarka 3D Prusa MK4', 'category_id' => $machinesCategory->id, 'value' => 4200, 'condition' => 'uzywany', 'stationary' => true, 'description' => 'Pole robocze 25x21x22 cm, PLA, PETG, ASA. Filament z szafki obok.'], $hallRoom);
        $laserCutter = $this->item(['name' => 'Ploter laserowy CO2 60 W', 'category_id' => $machinesCategory->id, 'value' => 9800, 'condition' => 'uzywany', 'stationary' => true, 'description' => 'Pole 60x40 cm. Cięcie sklejki do 6 mm i pleksi, grawer. Tylko po szkoleniu BHP.'], $hallRoom);

        $member = User::factory()->create(['name' => 'Ola Mazur', 'email' => 'uzytkownik@craty.test', 'role' => 'uzytkownik']);
        $this->at($today->copy()->subDays(2));
        // Dni liczone od dziś, żeby zawsze były nadchodzące rezerwacje.
        $bookings = [
            [$printer, $member, 0, '10:00', '12:30', 'prywatny', 'Obudowa do czujnika wilgotności'],
            [$printer, $this->admin, 0, '16:00', '19:00', 'zajecia', 'Koło robotyki: modelowanie 3D'],
            [$laserCutter, $this->worker, 1, '17:00', '20:00', 'warsztaty', 'Warsztaty grawerowania dla seniorów'],
            [$laserCutter, $this->worker, 3, '08:00', '09:30', 'konserwacja', 'Czyszczenie luster i soczewki'],
            [$printer, $member, 4, '14:00', '18:00', 'prywatny', 'Części do drona'],
            [$laserCutter, $member, 5, '11:00', '13:00', 'prywatny', 'Pudełko na prezent ze sklejki'],
            [$printer, $member, 9, '15:00', '17:00', 'prywatny', null],
        ];
        foreach ($bookings as [$machine, $who, $dayOffset, $from, $to, $purpose, $description]) {
            $day = $today->copy()->addDays($dayOffset)->format('Y-m-d');
            MachineBooking::create([
                'item_id' => $machine->id, 'user_id' => $who->id, 'user_name' => $who->name,
                'starts_at' => Carbon::parse("$day $from"), 'ends_at' => Carbon::parse("$day $to"),
                'purpose' => $purpose, 'description' => $description,
            ]);
        }

        // ---------- inwentaryzacje ----------
        // Zakończona: regał 3, półka 2 w hali. Szlifierka nie znaleziona,
        // profile aluminiowe leżały tutaj zamiast na regale 7.
        $this->at($today->copy()->subDays(4));
        $scopeShelf = LocationScope::forLocation($hallShelf);
        $done = Stocktake::create(['scope' => $scopeShelf->params, 'label' => $scopeShelf->label(), 'started_by' => $this->worker->id]);
        foreach ([[$drill, true], [$bits, true], [$meter, true], [$profiles, false]] as [$scanned, $inPlace]) {
            $done->scans()->create(['item_id' => $scanned->id, 'in_place' => $inPlace, 'scanned_by' => $this->worker->id]);
        }
        $expected = $done->expectedItemsQuery()->get(['id', 'status']);
        $done->update([
            'expected_item_ids' => $expected->pluck('id')->all(),
            'away_item_ids' => $expected->whereIn('status', Stocktake::AWAY_STATUSES)->pluck('id')->values()->all(),
            'finished_at' => now(), 'finished_by' => $this->worker->id,
        ]);

        // W toku: garaż, jedna rzecz już zeskanowana.
        $this->at($today->copy());
        $garageScope = LocationScope::forLocation(StorageLocation::where('warehouse_id', $garage->id)->whereNull('rack')->firstOrFail());
        $open = Stocktake::create(['scope' => $garageScope->params, 'label' => $garageScope->label(), 'started_by' => $this->admin->id]);
        $open->scans()->create(['item_id' => $ladder->id, 'in_place' => true, 'scanned_by' => $this->admin->id]);

        // Przedmioty z DemoDataSeeder powstają bez zalogowanego użytkownika, więc
        // ich historia pokazywałaby "system"; w przykładach dodała je Ewa.
        ItemHistory::whereNull('user_id')->update(['user_id' => $this->admin->id]);

        Carbon::setTestNow();
        // Bez zdarzenia wylogowania (nie ma czego zapisywać w dzienniku aktywności).
        auth()->forgetUser();
    }

    /** Cofnięcie zegara: wszystko, co zapisze się dalej, ma tę datę. */
    private function at(Carbon $moment): void
    {
        // Godziny rozrzucone, ale powtarzalne, żeby zrzuty ekranu nie zmieniały się bez potrzeby.
        Carbon::setTestNow($moment->copy()->setTime(9, 0)->addMinutes((++$this->tick * 47) % 480));
    }

    private function item(array $data, ?StorageLocation $location): Item
    {
        $category = isset($data['category_id']) ? Category::find($data['category_id']) : null;
        $item = Item::create([
            ...$data,
            'storage_location_id' => $location?->id,
            'inventory_no' => app(InventoryNumberGenerator::class)->generate($category, $location),
            'created_by' => $this->admin->id,
        ]);
        $item->qr_path = app(QrCodeGenerator::class)->generateForItem($item);
        $item->saveQuietly();

        foreach (['jpg', 'png'] as $ext) {
            $photo = storage_path('app/manual-photos/'.Str::slug($item->name).'.'.$ext);
            if (File::exists($photo)) {
                $path = 'items/'.$item->id.'/photo.'.$ext;
                Storage::disk('public')->put($path, File::get($photo));
                $item->photos()->create(['path' => $path, 'is_primary' => true, 'sort_order' => 0]);
                break;
            }
        }

        return $item;
    }

    private function inspect(Item $item, Carbon $performedAt, string $notes): void
    {
        $item->inspections()->create(['performed_at' => $performedAt, 'passed' => true, 'notes' => $notes, 'user_id' => $this->admin->id]);
        $item->update(['next_inspection_at' => $performedAt->copy()->addMonthsNoOverflow($item->inspection_interval_months)]);
    }

    private function sendForRepair(Item $item, Contact $service, string $fault, Carbon $sentAt, Carbon $expectedAt): Repair
    {
        $repair = $item->repairs()->create([
            'fault_description' => $fault, 'sent_at' => $sentAt, 'expected_at' => $expectedAt,
            'contact_id' => $service->id, 'service_name' => $service->name,
            'previous_status' => $item->status, 'created_by' => $this->admin->id,
        ]);
        $item->update(['status' => 'w_naprawie']);
        $item->histories()->create(['user_id' => $this->admin->id, 'action' => 'repair_sent', 'new_value' => $repair->serviceLabel()]);

        return $repair;
    }

    private function completeRepair(Repair $repair, Carbon $returnedAt, float $cost, string $workDone): void
    {
        $repair->update(['returned_at' => $returnedAt, 'cost' => $cost, 'work_done' => $workDone, 'closed_by' => $this->worker->id]);
        $repair->item->update(['status' => 'dostepny']);
        $repair->item->histories()->create(['user_id' => $this->worker->id, 'action' => 'repair_returned', 'old_value' => $repair->serviceLabel()]);
    }
}
