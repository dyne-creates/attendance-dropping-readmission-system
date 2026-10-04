<nav x-data="{ open: false }"
     class="bg-gray-950/95 backdrop-blur-xl border-b border-red-900/50 sticky top-0 z-50">

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <div class="flex justify-between h-16">

            <div class="flex">

                <div class="shrink-0 flex items-center">

                    <a href="{{ route('osa.dashboard') }}"
                       class="flex items-center gap-3 group">

                        <div>

                            <div class="text-lg font-bold text-white">
                                OSA Management
                            </div>

                            <div class="text-[10px] uppercase tracking-widest text-red-500">
                                Student Affairs
                            </div>

                        </div>

                    </a>

                </div>

                <div class="hidden space-x-2 sm:-my-px sm:ms-10 sm:flex">

                    <a href="{{ route('osa.dashboard') }}"
                       class="inline-flex items-center px-4 pt-1 text-sm font-medium border-b-2 transition
                       {{ request()->routeIs('osa.dashboard')
                            ? 'border-red-500 text-white'
                            : 'border-transparent text-gray-400 hover:text-white hover:border-red-800' }}">

                        Dashboard

                    </a>

                    <a href="{{ route('osa.readmissions') }}"
                       class="inline-flex items-center px-4 pt-1 text-sm font-medium border-b-2 transition
                       {{ request()->routeIs('osa.readmissions*')
                            ? 'border-red-500 text-white'
                            : 'border-transparent text-gray-400 hover:text-white hover:border-red-800' }}">

                        Readmissions

                    </a>

                    <a href="{{ route('osa.archive.drops') }}"
                       class="inline-flex items-center px-4 pt-1 text-sm font-medium border-b-2 transition
                       {{ request()->routeIs('osa.archive.drops')
                            ? 'border-red-500 text-white'
                            : 'border-transparent text-gray-400 hover:text-white hover:border-red-800' }}">

                        Drop Records

                    </a>

                    <a href="{{ route('osa.archive.readmissions') }}"
                       class="inline-flex items-center px-4 pt-1 text-sm font-medium border-b-2 transition
                       {{ request()->routeIs('osa.archive.readmissions')
                            ? 'border-red-500 text-white'
                            : 'border-transparent text-gray-400 hover:text-white hover:border-red-800' }}">

                        Readmission Archive

                    </a>

                </div>

            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">

                <x-dropdown align="right" width="48">

                    <x-slot name="trigger">

                        <button
                            class="flex items-center gap-3 px-3 py-2
                                   rounded-lg
                                   text-sm font-medium
                                   text-gray-300
                                   hover:text-white
                                   hover:bg-gray-900
                                   transition
                                   focus:outline-none">

                            <div class="hidden md:block">

                                {{ Auth::user()->name }}

                            </div>

                            <svg class="w-4 h-4 text-gray-500"
                                 fill="none"
                                 stroke="currentColor"
                                 viewBox="0 0 24 24">

                                <path
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                    stroke-width="2"
                                    d="M19 9l-7 7-7-7"/>

                            </svg>

                        </button>

                    </x-slot>

                    <x-slot name="content">

                        <div class="bg-gray-900 border border-gray-800">

                            <x-dropdown-link
                                :href="route('profile.edit')"
                                class="text-gray-300 hover:bg-gray-800 hover:text-white">

                                Profile

                            </x-dropdown-link>

                            <form method="POST"
                                  action="{{ route('logout') }}">

                                @csrf

                                <x-dropdown-link
                                    :href="route('logout')"
                                    onclick="event.preventDefault();
                                    this.closest('form').submit();"
                                    class="text-gray-300 hover:bg-gray-800 hover:text-white">

                                    Log Out

                                </x-dropdown-link>

                            </form>

                        </div>

                    </x-slot>

                </x-dropdown>

            </div>

            <div class="-me-2 flex items-center sm:hidden">

                <button
                    @click="open = ! open"
                    class="inline-flex items-center justify-center p-2
                           rounded-md
                           text-gray-400
                           hover:text-white
                           hover:bg-gray-900
                           focus:outline-none">

                    <svg class="h-6 w-6"
                         stroke="currentColor"
                         fill="none"
                         viewBox="0 0 24 24">

                        <path
                            :class="{
                                'hidden': open,
                                'inline-flex': !open
                            }"
                            class="inline-flex"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M4 6h16M4 12h16M4 18h16"/>

                        <path
                            :class="{
                                'hidden': !open,
                                'inline-flex': open
                            }"
                            class="hidden"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M6 18L18 6M6 6l12 12"/>

                    </svg>

                </button>

            </div>

        </div>

    </div>

    <div
        :class="{
            'block': open,
            'hidden': !open
        }"
        class="hidden sm:hidden bg-gray-950 border-t border-gray-900">

        <div class="pt-2 pb-3 space-y-1">

            <a href="{{ route('osa.dashboard') }}"
               class="block px-4 py-3 text-sm
               {{ request()->routeIs('osa.dashboard')
                    ? 'text-white bg-red-900/30 border-l-2 border-red-500'
                    : 'text-gray-400 hover:text-white hover:bg-gray-900' }}">

                Dashboard

            </a>

            <a href="{{ route('osa.readmissions') }}"
               class="block px-4 py-3 text-sm
               {{ request()->routeIs('osa.readmissions*')
                    ? 'text-white bg-red-900/30 border-l-2 border-red-500'
                    : 'text-gray-400 hover:text-white hover:bg-gray-900' }}">

                Readmissions

            </a>

            <a href="{{ route('osa.archive.drops') }}"
               class="block px-4 py-3 text-sm
               {{ request()->routeIs('osa.archive.drops')
                    ? 'text-white bg-red-900/30 border-l-2 border-red-500'
                    : 'text-gray-400 hover:text-white hover:bg-gray-900' }}">

                Drop Records

            </a>

            <a href="{{ route('osa.archive.readmissions') }}"
               class="block px-4 py-3 text-sm
               {{ request()->routeIs('osa.archive.readmissions')
                    ? 'text-white bg-red-900/30 border-l-2 border-red-500'
                    : 'text-gray-400 hover:text-white hover:bg-gray-900' }}">

                Readmission Archive

            </a>

        </div>

        <div class="pt-4 pb-4 border-t border-gray-900">

            <div class="px-4">

                <div class="font-medium text-base text-white">
                    {{ Auth::user()->name }}
                </div>

                <div class="font-medium text-sm text-gray-500">
                    {{ Auth::user()->email }}
                </div>

            </div>

            <div class="mt-3 space-y-1">

                <a href="{{ route('profile.edit') }}"
                   class="block px-4 py-3 text-sm text-gray-400 hover:text-white hover:bg-gray-900">

                    Profile

                </a>

                <form method="POST"
                      action="{{ route('logout') }}">

                    @csrf

                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault();
                       this.closest('form').submit();"
                       class="block px-4 py-3 text-sm text-gray-400 hover:text-white hover:bg-gray-900">

                        Log Out

                    </a>

                </form>

            </div>

        </div>

    </div>

</nav>
