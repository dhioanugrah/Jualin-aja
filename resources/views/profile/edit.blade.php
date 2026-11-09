<x-app-layout>
    <x-slot:title>Edit profil</x-slot:title>

    <div class="mx-auto max-w-2xl">
        <x-page-header title="Edit profil" :back="route('account')" />

        <div class="space-y-5">
            @include('profile.partials.update-profile-information-form')
            @include('profile.partials.update-password-form')
            @include('profile.partials.delete-user-form')
        </div>
    </div>
</x-app-layout>
