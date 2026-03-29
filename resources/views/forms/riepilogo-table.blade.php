<table class="w-full table-fixed border">
    <tbody>
        @foreach ($fields as $label => $value)
            <tr class="border-b">
                {{-- Colonna label fissa al 20% --}}
                <td class="bg-gray-100 text-gray-700 font-semibold px-3 py-2;" style="width:300px; font-size:15px;">
                    {{ $label }}
                </td>
                <td class="px-3 py-2" style="font-size:15px;">
                    {{ $value }}
                </td>

            </tr>
        @endforeach
    </tbody>
</table>
