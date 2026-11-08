@props(['status'])
@php $status = $status instanceof \App\Enums\ProposalStatus ? $status : \App\Enums\ProposalStatus::from($status); @endphp
<span {{ $attributes->merge(['class' => 'badge badge-'.$status->tone()]) }}>{{ $status->label() }}</span>
