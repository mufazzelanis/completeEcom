@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'w-full rounded-xl border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100 px-4 py-2.5 text-sm placeholder:text-gray-400 dark:placeholder:text-gray-500 shadow-sm outline-none transition focus:border-orange-500 dark:focus:border-orange-600 focus:ring-4 focus:ring-orange-500/10 dark:focus:ring-orange-600/20 disabled:bg-gray-50 dark:disabled:bg-gray-900 disabled:text-gray-400 disabled:cursor-not-allowed']) }}>
