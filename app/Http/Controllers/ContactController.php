<?php

namespace App\Http\Controllers;

use App\Dto\ContactData;
use App\Http\Requests\IndexContactRequest;
use App\Http\Requests\StoreContactRequest;
use App\Http\Requests\UpdateContactRequest;
use App\Http\ViewModels\ContactViewModel;
use App\Models\Contact;
use App\Services\ContactService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactService $contactService,
    ) {}

    public function index(IndexContactRequest $request): View
    {
        $contacts = $this->contactService->list(
            searchQuery: $request->validated('search'),
        );

        return view('contacts.index', [
            'contacts' => $contacts->through(static fn (Contact $contact) => new ContactViewModel($contact)),
            'search' => $request->validated('search'),
        ]);
    }

    public function create(): View
    {
        return view('contacts.create');
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $contact = $this->contactService->create(
            ContactData::fromRequest($request->validated()),
        );

        return redirect()
            ->route('contacts.show', new ContactViewModel($contact))
            ->with('success', 'Contact created successfully.');
    }

    public function show(Contact $contact): View
    {
        return view('contacts.show', [
            'contact' => new ContactViewModel($contact),
        ]);
    }

    public function edit(Contact $contact): View
    {
        return view('contacts.edit', [
            'contact' => new ContactViewModel($contact),
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->contactService->update(
            $contact,
            ContactData::fromRequest($request->validated()),
        );

        return redirect()
            ->route('contacts.show', new ContactViewModel($contact))
            ->with('success', 'Contact updated successfully.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->contactService->delete($contact);

        return redirect()
            ->route('contacts.index')
            ->with('success', 'Contact deleted successfully.');
    }
}
