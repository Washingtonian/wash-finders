<x-filament::page>
    <form wire:submit="save">
        {{ $this->form }}

        <div class="mt-6 flex justify-end">
            <x-filament::button type="submit">
                View Configuration Instructions
            </x-filament::button>
        </div>
    </form>

    <x-filament::section>
        <div class="space-y-4">
            <h3 class="text-lg font-semibold mb-4">How to Update Algolia Settings</h3>
            <div class="space-y-4 text-sm text-gray-600 dark:text-gray-400">
                <p>
                    Algolia settings are configured via environment variables in your <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">.env</code> file.
                </p>
                <div class="bg-white dark:bg-gray-900 p-4 rounded border">
                    <p class="font-mono text-xs space-y-1">
                        <div>SCOUT_DRIVER=algolia</div>
                        <div>SCOUT_PREFIX=</div>
                        <div>SCOUT_QUEUE=false</div>
                        <div>SCOUT_IDENTIFY=false</div>
                        <div>ALGOLIA_APP_ID=your_app_id_here</div>
                        <div>ALGOLIA_SECRET=your_secret_key_here</div>
                    </p>
                </div>
                <p>
                    After updating your <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">.env</code> file, run:
                </p>
                <div class="bg-white dark:bg-gray-900 p-4 rounded border">
                    <code class="font-mono text-xs">php artisan config:clear</code>
                </div>
                <p class="text-amber-600 dark:text-amber-400">
                    <strong>Note:</strong> Never commit your <code class="px-2 py-1 bg-gray-200 dark:bg-gray-700 rounded">.env</code> file or expose your Algolia Admin API Key publicly.
                </p>
            </div>
        </div>
    </x-filament::section>
</x-filament::page>

