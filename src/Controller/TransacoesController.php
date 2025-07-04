<?php

namespace App\Controller;

use App\Dto\TransacaoRealizarDto;
use App\Dto\UsuarioDto;
use App\Entity\Transacao;
use App\Repository\ContaRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class TransacoesController extends AbstractController
{
    #[Route('/transacoes', name: 'transacoes_realizar', methods: ['POST'])]
    public function realizar(
        #[MapRequestPayload(acceptFormat:'json')]
        TransacaoRealizarDto $entrada,
        UsuarioDto $usuarioDto,

        ContaRepository $contaRepository,
        EntityManagerInterface $entityManager

    ): Response 
    {
        $erros=[];
        // validar o DTO de entrada
        if (!$entrada->getIdUsuarioOrigem()) {
            array_push($erros, [
                'message' => 'Conta de origem é obrigatória!'
            ]);
        }
        if (!$entrada->getIdUsuarioDestino()) {
            array_push($erros, [
                'message' => 'Conta de destino é obrigatória!'
            ]);
        }
        if (!$entrada->getValor() ) {
            array_push($erros, [
                'message'=> 'Valor é obrigatório!'
            ]);
        }
        if ((float)$entrada->getValor() <= 0) {
            array_push($erros, [
                'message'=> 'O valor deve ser maior que zero!'
            ]);
        }
        if ($entrada->getIdUsuarioOrigem() === $entrada->getIdUsuarioDestino()){
            array_push($erros, [
                'message'=> 'As contas devem ser diferentes'
            ]);
        } 
        if (count($erros) > 0) {
            return $this->json($erros,422);
        }
        // validações de regra de negocio
        // validar se as contas existem

        $contaOrigem = $contaRepository->findByUsuarioId($entrada->getIdUsuarioOrigem());
        if (!$contaOrigem) {
            return $this->json([
                'message'=> 'Conta de origem não encontrada'
            ], 404);
        }

        $contaDestino = $contaRepository->findByUsuarioId($entrada->getIdUsuarioDestino());
        if (!$contaDestino) {
            return $this->json([
                'message'=> 'Conta de destino não encontrada'
            ], 404);

        }
        // validar se a origem tem saldo suficiente

        if ((float) $contaOrigem->getSaldo()< (float) $entrada->getValor()) {

            return $this->json([
                'message'=> 'Saldo insuficiente'
            ]);
        }

        // realizar a transação e salvar no banco

        $saldo = (float) $contaOrigem->getSaldo();
        $valorT = (float) $entrada->getValor();
        $saldoDestino = (float) $contaDestino->getSaldo();

        $contaOrigem->setSaldo($saldo - $valorT);
        $entityManager->persist($contaOrigem);


        $contaDestino->setSaldo($valorT + $saldoDestino);
        $entityManager->persist($contaDestino);

        $transacao = new Transacao();
        $transacao->setDataHora(new DateTime());
        $transacao->setValor($entrada->getValor());
        $transacao->setContaOrigem($contaDestino);
        $transacao->setContaDestino($contaDestino);
        $entityManager->persist($transacao);
        $entityManager->flush();

        return new Response(status: 204);

    }
}