<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Moduł Kontakty (App\Support\Modules, `module:contacts`): lista, karta z
 * historią wypożyczeń i formularz. Usunięcie kontaktu nie rusza wypożyczeń,
 * zostaje przy nich nazwa zapisana w chwili wypożyczenia (borrower_name).
 */
class ContactController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q', ''));
        $type = $request->query('type');

        $contacts = Contact::query()
            ->withCount(['loans', 'activeLoans'])
            ->when($search !== '', fn ($query) => $query->where(fn ($q) => $q
                ->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->when(array_key_exists((string) $type, Contact::TYPES), fn ($query) => $query->where('type', $type))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('contacts.index', compact('contacts', 'search', 'type'));
    }

    public function create()
    {
        return view('contacts.form', ['contact' => new Contact]);
    }

    public function store(Request $request)
    {
        $contact = Contact::create($this->validated($request));

        return redirect()->route('contacts.show', $contact)->with('status', __('Contact added.'));
    }

    public function show(Contact $contact)
    {
        $loans = $contact->loans()->with('item')->latest('borrowed_at')->get();

        return view('contacts.show', compact('contact', 'loans'));
    }

    public function edit(Contact $contact)
    {
        return view('contacts.form', compact('contact'));
    }

    public function update(Request $request, Contact $contact)
    {
        $contact->update($this->validated($request));

        return redirect()->route('contacts.show', $contact)->with('status', __('Contact saved.'));
    }

    public function destroy(Contact $contact)
    {
        $contact->delete();

        return redirect()->route('contacts.index')->with('status', __('Contact deleted.'));
    }

    /** @return array<string, mixed> */
    private function validated(Request $request): array
    {
        $data = $request->validate(self::rules());
        $data['type'] ??= 'osoba';

        return $data;
    }

    /** Wspólne z szybkim dodawaniem kontaktu w formularzu wypożyczenia (LoanController). */
    public static function rules(string $prefix = ''): array
    {
        return [
            $prefix.'name' => ['required', 'string', 'max:255'],
            $prefix.'type' => ['nullable', Rule::in(array_keys(Contact::TYPES))],
            $prefix.'phone' => ['nullable', 'string', 'max:50'],
            $prefix.'email' => ['nullable', 'email', 'max:255'],
            $prefix.'address' => ['nullable', 'string', 'max:1000'],
            $prefix.'notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
