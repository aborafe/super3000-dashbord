@php
    $user = auth()->user();
    $unreadCount = $user?->unreadNotifications()->count() ?? 0;
    $recentNotifications = $user?->notifications()->latest()->limit(5)->get() ?? collect();
    $currentTheme = session('theme', 'light');
@endphp

<header class="h-16 flex items-center justify-between px-4 md:px-6 bg-white/80 backdrop-blur border-b border-slate-200 dark:bg-slate-900/80 dark:border-slate-700">
    <div class="flex items-center gap-3">
        <button type="button" class="md:hidden inline-flex items-center justify-center p-2 rounded-md border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600 dark:text-slate-100">
            <span class="sr-only">{{ __('Toggle sidebar') }}</span>
            ☰
        </button>

        <div>
            <div class="text-sm text-slate-500 dark:text-slate-400">
                {{ __('Welcome back,') }}
            </div>
            <div class="font-semibold">
                {{ auth()->user()->name ?? '' }}
            </div>
        </div>
    </div>

    <div class="flex items-center gap-3">
        <details class="relative">
            <summary
                class="list-none inline-flex items-center gap-2 px-3 py-1.5 rounded-md text-sm border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600 cursor-pointer">
                <span aria-hidden="true">🔔</span>
                <span class="sr-only">{{ __('Notifications') }}</span>
                @if ($unreadCount > 0)
                    <span
                        class="inline-flex min-w-5 justify-center rounded-full bg-rose-600 px-1.5 text-xs font-semibold text-white">
                        {{ $unreadCount }}
                    </span>
                @endif
            </summary>

            <div
                class="absolute right-0 z-20 mt-2 w-80 rounded-md border border-slate-200 bg-white shadow-lg dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-center justify-between border-b border-slate-100 px-4 py-2 text-sm font-semibold dark:border-slate-800">
                    <span>{{ __('Notifications') }}</span>
                    @if ($unreadCount > 0)
                        <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                            @csrf
                            <button type="submit" class="text-xs text-sky-600 hover:text-sky-700">
                                {{ __('Mark all read') }}
                            </button>
                        </form>
                    @endif
                </div>

                <div class="max-h-80 overflow-y-auto">
                    @forelse ($recentNotifications as $notification)
                        @php
                            $data = $notification->data ?? [];
                            $title = $data['title'] ?? __('Notification');
                            $message = $data['message'] ?? '';
                            $routeName = $data['route'] ?? null;
                            $routeParams = $data['route_params'] ?? [];
                            $hasParams = collect($routeParams)->every(fn ($value) => ! is_null($value));
                            $url = $routeName && $hasParams ? route($routeName, $routeParams) : ($data['url'] ?? null);
                        @endphp

                        <div
                            class="border-b border-slate-100 px-4 py-3 text-sm dark:border-slate-800 {{ $notification->read_at ? '' : 'bg-slate-50 dark:bg-slate-800/60' }}">
                            <div class="font-medium text-slate-800 dark:text-slate-100">
                                {{ $title }}
                            </div>
                            @if ($message)
                                <div class="text-xs text-slate-500 dark:text-slate-400">
                                    {{ $message }}
                                </div>
                            @endif
                            <div class="mt-1 flex items-center justify-between gap-2 text-xs text-slate-400">
                                <span>{{ $notification->created_at?->diffForHumans() }}</span>
                                <div class="flex items-center gap-2">
                                    @if ($url)
                                        <a href="{{ $url }}" class="text-sky-600 hover:text-sky-700">
                                            {{ __('View') }}
                                        </a>
                                    @endif
                                    @if (! $notification->read_at)
                                        <form method="POST" action="{{ route('admin.notifications.read', $notification->id) }}">
                                            @csrf
                                            <button type="submit" class="text-slate-500 hover:text-slate-700">
                                                {{ __('Mark read') }}
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="px-4 py-6 text-center text-sm text-slate-500 dark:text-slate-400">
                            {{ __('No notifications.') }}
                        </div>
                    @endforelse
                </div>
            </div>
        </details>

        <form method="POST" action="{{ route('admin.theme.toggle') }}"
            x-data="{ theme: localStorage.getItem('theme') || '{{ $currentTheme }}' }">
            @csrf
            <input type="hidden" name="theme" x-model="theme">
            <button
                type="submit"
                @click="
                    theme = theme === 'dark' ? 'light' : 'dark';
                    localStorage.setItem('theme', theme);
                    document.documentElement.classList.toggle('dark', theme === 'dark');
                "
                class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-sm border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600"
            >
                <span
                    x-text="theme === 'dark' ? '{{ __('Light') }}' : '{{ __('Dark') }}'">
                    {{ $currentTheme === 'dark' ? __('Light') : __('Dark') }}
                </span>
            </button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button
                type="submit"
                class="inline-flex items-center justify-center px-3 py-1.5 rounded-md text-sm border border-slate-300 bg-white hover:bg-slate-50 dark:bg-slate-800 dark:border-slate-600"
            >
                {{ __('Logout') }}
            </button>
        </form>
    </div>
</header>

