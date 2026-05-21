@php
    $asset = $asset ?? null;
    $featureEntries = $asset ? $asset->resolveFeatureEntries() : [];
@endphp
@if($asset)
    <td>{{ $asset->resolveDisplayModel() }}</td>
    <td style="min-width: 180px;">
        @if(count($featureEntries) > 0)
            <ul class="mb-0 ps-3" style="font-size: 12px;">
                @foreach($featureEntries as $entry)
                    <li><strong>{{ $entry['label'] }}</strong>: {{ $entry['value'] }}</li>
                @endforeach
            </ul>
        @else
            <span class="text-muted">N/A</span>
        @endif
    </td>
@else
    <td>N/A</td>
    <td>N/A</td>
@endif
