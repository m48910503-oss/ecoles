<div {{ $attributes->class('table-shell') }}>
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-gray-800">
            {{ $slot }}
        </table>
    </div>
</div>
