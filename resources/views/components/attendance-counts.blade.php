@props(['present' => 0, 'late' => 0, 'absent' => 0, 'total' => null])

<dl {{ $attributes->class(['flex flex-wrap items-center gap-x-5 gap-y-1 text-sm tabular-nums']) }}>
    @unless (is_null($total))
        <div class="flex items-baseline gap-1.5">
            <dt class="muted">Class days</dt>
            <dd class="font-medium">{{ $total }}</dd>
        </div>
    @endunless

    <div class="flex items-center gap-1.5">
        <dt class="muted flex items-center gap-1.5">
            <span class="tick-key tick-present" aria-hidden="true"></span>
            Present
        </dt>
        <dd class="font-medium">{{ $present }}</dd>
    </div>

    <div class="flex items-center gap-1.5">
        <dt class="muted flex items-center gap-1.5">
            <span class="tick-key tick-late" aria-hidden="true"></span>
            Late
        </dt>
        <dd class="font-medium">{{ $late }}</dd>
    </div>

    <div class="flex items-center gap-1.5">
        <dt class="muted flex items-center gap-1.5">
            <span class="tick-key tick-absent" aria-hidden="true"></span>
            Absent
        </dt>
        <dd class="font-medium">{{ $absent }}</dd>
    </div>
</dl>