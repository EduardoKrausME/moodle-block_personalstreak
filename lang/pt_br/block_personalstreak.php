<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Strings em português do Brasil para block_personalstreak.
 *
 * @package    block_personalstreak
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

$string['active'] = 'Ativo';
$string['active30'] = 'Dias ativos nos últimos 30 dias: {$a}';
$string['activitysettings'] = 'O que conta como estudo';
$string['activitysettings_desc'] = 'Estes são os padrões do site. O professor pode sobrescrevê-los nas configurações do bloco em cada curso.';
$string['blocksettings'] = 'Configurações de consistência de estudos';
$string['customevents'] = 'Eventos Moodle configuráveis';
$string['customevents_desc'] = 'Informe uma classe de evento completa por linha, por exemplo \\mod_lesson\\event\\lesson_ended.';
$string['customevents_help'] = 'Informe uma classe de evento completa por linha, por exemplo \\mod_lesson\\event\\lesson_ended.';
$string['daymode'] = 'Dias ignorados';
$string['daymode_custom'] = 'Ignorar dias selecionados da semana';
$string['daymode_desc'] = 'Define se todos os dias são exigidos ou se alguns dias não devem quebrar a sequência.';
$string['daymode_normal'] = 'Contar todos os dias normalmente';
$string['daymode_weekends'] = 'Ignorar sábado e domingo';
$string['event_personal_record_reached'] = 'Recorde pessoal de sequência atingido';
$string['event_streak_broken'] = 'Sequência pessoal interrompida';
$string['event_streak_continued'] = 'Sequência pessoal continuada';
$string['event_streak_milestone_reached'] = 'Marco de sequência pessoal atingido';
$string['event_streak_started'] = 'Sequência pessoal iniciada';
$string['ignored'] = 'Dia ignorado';
$string['ignoreweekdays'] = 'Dias da semana a ignorar';
$string['ignoreweekdays_desc'] = 'No modo personalizado, use números ISO de 1 a 7 separados por vírgula, onde segunda-feira é 1 e domingo é 7.';
$string['inactive'] = 'Sem atividade';
$string['last30days'] = 'Seus últimos 30 dias';
$string['milestonerewardlabel'] = 'Marco de sequência pessoal: {$a} dias';
$string['milestones'] = 'Marcos';
$string['milestones_desc'] = 'Um marco por linha no formato dias|xp|créditos|badgeid|evento|visual. Exemplo: 7|50|20|0|1|1. Use 0 para desativar uma recompensa. Os marcos padrão são apenas visuais.';
$string['milestones_help'] = 'Um marco por linha no formato dias|xp|créditos|badgeid|evento|visual. Exemplo: 7|50|20|0|1|1. Use 0 para desativar uma recompensa. Os marcos padrão são apenas visuais.';
$string['nextmilestone'] = 'Próximo marco';
$string['nextmilestonevalue'] = '{$a} dias consecutivos';
$string['notstudiedtoday'] = 'Ainda não foi registrada uma atividade de estudo válida hoje.';
$string['personalrecord'] = 'Recorde pessoal: {$a} dias';
$string['personalstreak:addinstance'] = 'Adicionar bloco de consistência de estudos';
$string['personalstreak:manage'] = 'Gerenciar configurações de consistência de estudos';
$string['personalstreak:myaddinstance'] = 'Adicionar bloco de consistência de estudos ao Painel';
$string['personalstreak:viewown'] = 'Visualizar a própria consistência de estudos';
$string['pluginname'] = 'Consistência de estudos';
$string['privacy:metadata:days'] = 'Armazena a atividade diária consolidada usada no cálculo de consistência.';
$string['privacy:metadata:days:activitycount'] = 'Quantidade de eventos de estudo válidos registrados no dia.';
$string['privacy:metadata:days:courseid'] = 'Curso em que a atividade ocorreu.';
$string['privacy:metadata:days:daydate'] = 'Dia do calendário no fuso horário do usuário.';
$string['privacy:metadata:days:firstactivity'] = 'Data e hora da primeira atividade válida do dia.';
$string['privacy:metadata:days:lastactivity'] = 'Data e hora da última atividade válida do dia.';
$string['privacy:metadata:days:source'] = 'Tipos de sinais de estudo registrados no dia.';
$string['privacy:metadata:days:userid'] = 'Usuário ao qual pertence o dia de atividade.';
$string['privacy:metadata:events'] = 'Armazena hashes de idempotência dos eventos de sequência.';
$string['privacy:metadata:events:daydate'] = 'Dia do calendário do usuário associado ao evento protegido.';
$string['privacy:metadata:events:eventkey'] = 'Chave semântica usada para impedir evento de sequência duplicado.';
$string['privacy:metadata:events:uniquehash'] = 'Hash de idempotência usado para impedir eventos duplicados.';
$string['privacy:metadata:mstone'] = 'Armazena o estado de entrega dos marcos para evitar recompensas e eventos duplicados.';
$string['privacy:metadata:mstone:badgeawarded'] = 'Indica se a recompensa de badge configurada foi entregue.';
$string['privacy:metadata:mstone:creditsrewarded'] = 'Indica se a recompensa de créditos configurada foi entregue.';
$string['privacy:metadata:mstone:eventfired'] = 'Indica se o evento Moodle configurado para o marco foi disparado.';
$string['privacy:metadata:mstone:milestonedays'] = 'Tamanho da sequência pessoal representada pelo marco.';
$string['privacy:metadata:mstone:xprewarded'] = 'Indica se a recompensa de XP configurada foi entregue.';
$string['privacy:metadata:state'] = 'Armazena o estado agregado da sequência pessoal.';
$string['privacy:metadata:state:beststreak'] = 'Maior sequência pessoal já atingida.';
$string['privacy:metadata:state:currentstreak'] = 'Tamanho da sequência pessoal atual.';
$string['privacy:metadata:state:lastactiveday'] = 'Dia ativo mais recente no fuso do usuário.';
$string['privacy:metadata:state:longeststreakend'] = 'Último dia ativo da maior sequência pessoal.';
$string['privacy:metadata:state:longeststreakstart'] = 'Primeiro dia da maior sequência pessoal.';
$string['privacy:metadata:state:totalactivedays'] = 'Quantidade total de dias ativos armazenados.';
$string['protection_none'] = 'Sem proteção';
$string['protection_one'] = '1 dia de tolerância por sequência';
$string['protection_rolling'] = 'N dias de tolerância em um período móvel';
$string['protectiondays'] = 'Dias de tolerância';
$string['protectiondays_desc'] = 'Quantidade máxima de dias perdidos protegidos dentro do período configurado.';
$string['protectionmode'] = 'Proteção da sequência';
$string['protectionmode_desc'] = 'A proteção não cria atividade falsa. Ela apenas impede que um dia obrigatório perdido zere a sequência existente.';
$string['protectionperiod'] = 'Período da proteção em dias';
$string['protectionperiod_desc'] = 'Janela móvel usada para limitar os dias de tolerância.';
$string['streakheadline'] = '{$a} dias de aprendizado contínuo';
$string['studiedtoday'] = 'Você estudou hoje.';
$string['today'] = 'Hoje';
$string['trackcompletion'] = 'Conclusão de atividade';
$string['trackcompletion_desc'] = 'Conta quando o Moodle registra uma atividade como concluída.';
$string['trackcompletion_help'] = 'Conta quando o Moodle registra uma atividade como concluída.';
$string['trackcourseaccess'] = 'Acesso ao curso';
$string['trackcourseaccess_desc'] = 'Conta a abertura do curso como sinal de estudo. Apenas fazer login no Moodle nunca é suficiente.';
$string['trackcourseaccess_help'] = 'Conta a abertura do curso como sinal de estudo. Apenas fazer login no Moodle nunca é suficiente.';
$string['trackforum'] = 'Postagem em fórum';
$string['trackforum_desc'] = 'Conta a criação de uma postagem em fórum.';
$string['trackforum_help'] = 'Conta a criação de uma postagem em fórum.';
$string['trackpersonalxp'] = 'Atividade registrada pelo Personal XP';
$string['trackpersonalxp_desc'] = 'Aceita sinais de estudo emitidos pelo local_personalxp ou enviados pela API pública deste bloco.';
$string['trackpersonalxp_help'] = 'Aceita sinais de estudo emitidos pelo local_personalxp ou enviados pela API pública deste bloco.';
$string['trackquiz'] = 'Tentativa de quiz';
$string['trackquiz_desc'] = 'Conta uma tentativa de quiz enviada.';
$string['trackquiz_help'] = 'Conta uma tentativa de quiz enviada.';
$string['trackresourceview'] = 'Visualização de recurso';
$string['trackresourceview_desc'] = 'Conta visualizações de recursos passivos como Página, Livro, Arquivo, URL e Pasta.';
$string['trackresourceview_help'] = 'Conta visualizações de recursos passivos como Página, Livro, Arquivo, URL e Pasta.';
$string['tracksubmission'] = 'Envio de atividade';
$string['tracksubmission_desc'] = 'Conta o envio de uma tarefa.';
$string['tracksubmission_help'] = 'Conta o envio de uma tarefa.';
