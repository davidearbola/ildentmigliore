
<tr>
    {{-- Aggiungiamo lo stile "text-align: center;" direttamente alla cella <td> --}}
    <td class="header" style="text-align: center;">
        <a href="{{ $url }}" style="display: inline-block;">
            {{ $slot }}
        </a>
    </td>
</tr>
