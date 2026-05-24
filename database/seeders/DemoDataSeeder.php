<?php

namespace Database\Seeders;


use App\Models\AnoLetivo;
use App\Models\AreaFormacao;
use App\Models\AvaliacaoContinua;
use App\Models\Curso;
use App\Models\Disciplina;
use App\Models\Nota;
use App\Models\Role;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {

        $roles = Role::all()->keyBy('name');

        User::firstOrCreate(['email' => 'admin@escola.ao'], ['name' => 'Administrador do Sistema', 'password' => Hash::make('password'), 'role_id' => $roles['admin']->id, 'ativo' => true]);
        User::firstOrCreate(['email' => 'secretaria@escola.ao'], ['name' => 'Fátima Cardoso', 'password' => Hash::make('password'), 'role_id' => $roles['secretaria']->id, 'telefone' => '923456789', 'genero' => 'F', 'ativo' => true]);

        $profDados = [
            ['Matemática','prof.mat@escola.ao','M'],['Física','prof.fis@escola.ao','F'],['Química','prof.qui@escola.ao','M'],['Biologia','prof.bio@escola.ao','F'],
            ['Português','prof.port@escola.ao','M'],['Inglês','prof.ing@escola.ao','F'],['História','prof.hist@escola.ao','M'],['Geografia','prof.geo@escola.ao','F'],
            ['Educação Física','prof.ef@escola.ao','M'],['TIC','prof.tic@escola.ao','F'],['Filosofia','prof.fil@escola.ao','M'],['Empreendedorismo','prof.emp@escola.ao','F'],
        ];

        foreach ($profDados as $i => [$nome,$email,$genero]) {
            $professores[] = User::firstOrCreate(['email'=>$email],[
                'name'=>"Prof. {$nome}", 'password'=>Hash::make('password'), 'role_id'=>$roles['professor']->id,
                'bi'=>'006'.str_pad((string)($i+1),9,'0',STR_PAD_LEFT).'LA041','telefone'=>'923'.str_pad((string)($i+1),6,'0',STR_PAD_LEFT),'genero'=>$genero,'ativo'=>true,
            ]);
        }

        $alunos=[];
        for($i=1;$i<=30;$i++){
            $alunos[] = User::firstOrCreate(['email'=>"aluno{$i}@escola.ao"],[
                'name'=>"Aluno {$i}",'password'=>Hash::make('password'),'role_id'=>$roles['aluno']->id,
                'numero_processo'=>'2023'.str_pad((string)$i,3,'0',STR_PAD_LEFT),'bi'=>'007'.str_pad((string)$i,9,'0',STR_PAD_LEFT).'LA041',
                'data_nascimento'=>'2007-01-01','genero'=>$i%2===0?'F':'M','telefone'=>'924'.str_pad((string)$i,6,'0',STR_PAD_LEFT),
                'nome_encarregado'=>"Encarregado {$i}",'contacto_encarregado'=>'923'.str_pad((string)(300000+$i),6,'0',STR_PAD_LEFT),'ativo'=>true,
            ]);
        }
        $anos=[
            ['2023/2024','2023-09-01','2024-07-31',true],
            ['2024/2025','2024-09-01','2025-07-31',true],
            ['2025/2026','2025-09-01','2026-07-31',true],
            ['2026/2027','2026-09-01','2027-07-31',false],
        ];
        $anosIds=[];
        AnoLetivo::query()->update(['ativo'=>false]);
        foreach($anos as $idx=>[$nome,$ini,$fim,$enc]){
            $ano=AnoLetivo::updateOrCreate(['nome'=>$nome],['data_inicio'=>$ini,'data_fim'=>$fim,'encerrado'=>$enc,'ativo'=>$idx===3]);
            $anosIds[]=$ano->id;
        }


            $cfb = Curso::firstOrCreate(['codigo'=>'CFB'],['nome'=>'Ciências Físicas e Biológicas','coordenador_id'=>$professores[0]->id,'ativo'=>true]);
        $cej = Curso::firstOrCreate(['codigo'=>'CEJ'],['nome'=>'Ciências Económicas e Jurídicas','coordenador_id'=>$professores[4]->id,'ativo'=>true]);
        $area = AreaFormacao::firstOrCreate(['nome'=>'Ciencias'],['descricao'=>'Cursos da area de ciencias gerais.','ativo'=>true]);
        foreach([$cfb,$cej] as $curso){ $curso->update(['area_formacao_id'=>$area->id]); }

        $discData=[
            ['MAT','Matemática',1,1,1,1,0],['FIS','Física',1,1,1,1,0],['QUI','Química',1,1,1,1,0],['BIO','Biologia',1,1,1,1,0],
            ['LP','Língua Portuguesa',1,1,1,1,0],['ING','Inglês',1,1,1,1,0],['HIS','História',1,1,1,1,0],['GEO','Geografia',1,1,1,1,0],
            ['EF','Educação Física',1,1,1,1,0],['TIC','TIC',1,0,0,0,1],['FIL','Filosofia',0,1,1,1,0],['EMP','Empreendedorismo',0,0,1,1,1],
        ];

        $disciplinas=[];
        foreach($discData as [$cod,$nome,$l10,$l11,$l12,$l13,$terminal]){
            $disciplinas[$cod]=Disciplina::firstOrCreate(['codigo'=>$cod],[
                'nome'=>$nome,'leciona_10'=>$l10,'leciona_11'=>$l11,'leciona_12'=>$l12,'leciona_13'=>$l13,'disciplina_terminal'=>$terminal,'ativo'=>true,
            ]);
        }

        $profMap=['MAT'=>0,'FIS'=>1,'QUI'=>2,'BIO'=>3,'LP'=>4,'ING'=>5,'HIS'=>6,'GEO'=>7,'EF'=>8,'TIC'=>9,'FIL'=>10,'EMP'=>11];
        $classDiscs=['10'=>['MAT','FIS','QUI','BIO','LP','ING','HIS','GEO','EF','TIC'],'11'=>['MAT','FIS','QUI','BIO','LP','ING','HIS','GEO','EF','FIL'],'12'=>['MAT','FIS','QUI','BIO','LP','ING','HIS','GEO','EF','FIL','EMP'],'13'=>['MAT','FIS','QUI','BIO','LP','ING','HIS','GEO','EF','FIL','EMP']];

        // cohorts 15+15 always in A/B across 4 years 10->13
        for($year=0;$year<4;$year++){
            $classe=(string)(10+$year);
            $anoId=$anosIds[$year];
            foreach([['A',$cfb,array_slice($alunos,0,15)],['B',$cej,array_slice($alunos,15,15)]] as [$nome,$curso,$grupo]){
                $turma=Turma::firstOrCreate(['nome'=>$nome,'classe'=>$classe,'curso_id'=>$curso->id,'ano_letivo_id'=>$anoId],[
                    'coordenador_turma_id'=>$professores[$nome==='A'?0:4]->id,'capacidade'=>40,'turno'=>'M','ativo'=>true,
                ]);
                $ids=array_map(fn($c)=>$disciplinas[$c]->id,$classDiscs[$classe]);
                $turma->disciplinas()->syncWithoutDetaching($ids);
                foreach($classDiscs[$classe] as $cod){
                    $profId=$professores[$profMap[$cod]]->id;
                    DB::table('professor_turma_disciplina')->updateOrInsert(
                        ['professor_id'=>$profId,'turma_id'=>$turma->id,'disciplina_id'=>$disciplinas[$cod]->id,'ano_letivo_id'=>$anoId],
                        ['updated_at'=>now(),'created_at'=>now()]
                    );
                }

                foreach($grupo as $idx=>$aluno){
                    $status='matriculado';
                    if($year<3){
                        if(in_array($idx,[1,9],true)) $status='reprovado';
                        if(in_array($idx,[3,12],true)) $status='recurso';
                        if(in_array($idx,[5],true)) $status='concluido';
                        if($idx===3 && $year===1) $status='aprovado'; // recurso e passou no 2º ano
                    }
                    DB::table('turma_aluno')->updateOrInsert(
                        ['turma_id'=>$turma->id,'aluno_id'=>$aluno->id],
                        ['data_matricula'=>now()->subYears(3-$year)->toDateString(),'status'=>$status,'updated_at'=>now(),'created_at'=>now()]
                    );

                    foreach($ids as $discId){
                        $base = 11.5 + (($idx%5)-2); // distribuição estável
                        if(in_array($idx,[1,9],true)) $base=8.2; // reprovados
                        if(in_array($idx,[3,12],true)) $base=9.0; // potencial recurso
                        $pg=max(0,min(20,round($base+0.4,2)));
                        $cf=max(0,min(20,round($base,2)));
                        $cfd=max(0,min(20,round($base,2)));
                        $notaRecurso=null;
                        if(in_array($idx,[3,12],true)){
                            $notaRecurso = $idx===3 ? 11.2 : 9.3; // um passa e outro não
                        }

                        Nota::updateOrCreate(
                            ['aluno_id'=>$aluno->id,'turma_id'=>$turma->id,'disciplina_id'=>$discId,'ano_letivo_id'=>$anoId],
                            [
                                'mac1'=>$cf,'pp1'=>$cf,'pt1'=>$cf,'mt1'=>$cf,'mac2'=>$cf,'pp2'=>$cf,'pt2'=>$cf,'mt2'=>$cf,
                                'mft2'=>$cf,'mac3'=>$cf,'pp3'=>$cf,'pg'=>$pg,'mt3'=>$cf,'cf'=>$cf,'ca'=>$cf,'cfd'=>$cfd,
                                'nota_recurso'=>$notaRecurso,'status'=>'finalizado'
                            ]
                        );
                    }
                }


            }
            }

        $professorPadrao = $professores[0];
        Nota::query()->limit(120)->get()->each(function(Nota $nota) use ($professorPadrao){
            foreach([1,2,3] as $tri){
                AvaliacaoContinua::updateOrCreate(
                    ['nota_id'=>$nota->id,'trimestre'=>$tri,'descricao'=>"AC {$tri}.1"],
                    ['professor_id'=>$professorPadrao->id,'valor'=>max(0,min(20,round(((float)$nota->{'mac'.$tri})-0.5,2))),'data_avaliacao'=>now()->subDays(20)]
                );
                AvaliacaoContinua::updateOrCreate(
                    ['nota_id'=>$nota->id,'trimestre'=>$tri,'descricao'=>"AC {$tri}.2"],
                    ['professor_id'=>$professorPadrao->id,'valor'=>max(0,min(20,round(((float)$nota->{'mac'.$tri})+0.5,2))),'data_avaliacao'=>now()->subDays(10)]
                );
            }
        });
    }
}
