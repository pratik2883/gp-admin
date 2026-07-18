<x-filament::widget>
    <x-filament::card>
        <div class="flex items-center justify-between mb-2">
            <h2 class="text-base font-semibold">Recent notifications</h2>
        </div>

        @php $notifications = $this->notifications; @endphp

        @if ($notifications->isEmpty())
            <p class="text-sm text-gray-500">No notifications yet.</p>
        @else
            <ul class="divide-y divide-gray-200 text-sm">
                @foreach ($notifications as $notification)
                    @php $data = $notification->data ?? []; @endphp
                    @php
                        $referralId = $data['referral_id'] ?? null;
                        $gpId = $data['gp_id'] ?? null;
                        $specialistId = $data['specialist_id'] ?? null;
                        $url = null;
                        if ($referralId) {
                            $url = url('/admin/referrals');
                        } elseif ($gpId) {
                            $url = url('/admin/gps');
                        } elseif ($specialistId) {
                            $url = url('/admin/specialists');
                        }
                    @endphp
                    <li class="py-2 flex items-start justify-between">
                        <div>
                            @if ($url)
                                <a href="{{ $url }}" class="block hover:text-primary-600">
                                    <div class="font-medium">
                                        {{ $data['title'] ?? class_basename($notification->type) }}
                                    </div>
                                    <div class="text-gray-600">
                                        {{ $data['body'] ?? '' }}
                                    </div>
                                </a>
                            @else
                                <div class="font-medium">
                                    {{ $data['title'] ?? class_basename($notification->type) }}
                                </div>
                                <div class="text-gray-600">
                                    {{ $data['body'] ?? '' }}
                                </div>
                            @endif
                            <div class="text-xs text-gray-400">
                                {{ optional($notification->created_at)->diffForHumans() }}
                            </div>
                        </div>
                        @if (is_null($notification->read_at))
                            <span class="ml-2 inline-flex h-2 w-2 rounded-full bg-primary-500"></span>
                        @endif
                    </li>
                @endforeach
            </ul>
        @endif
    </x-filament::card>
</x-filament::widget>
