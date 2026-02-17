<?php

namespace Domain\Enums;

enum OccurrenceType: string
{
    case FIRE = 'incendio_urbano';
    case RESCUE = 'resgate_veicular';
    case MEDICAL = 'atendimento_pre_hospitalar';
    case AQUATIC = 'salvamento_aquatico';
    case FALSE_ALARM = 'falso_chamado';
}
