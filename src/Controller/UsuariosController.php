<?php

namespace App\Controller;

use App\Dto\UsuarioContaDto;
use App\Dto\UsuarioDto;
use App\Entity\Conta;
use App\Entity\Usuario;
use App\Repository\ContaRepository;
use App\Repository\UsuarioRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/api')]
final class UsuariosController extends AbstractController
{
    #[Route('/usuarios', name: 'usuarios_criar', methods: ['POST'])]
    public function criar(
        #[MapRequestPayload(acceptFormat: 'json')]
        UsuarioDto $usuarioDto,

        EntityManagerInterface $entitymanager,

        UsuarioRepository $usuarioRepository

    ): JsonResponse {
        $erros = [];

        if ((!$usuarioDto->getCpf())) {
            array_push($erros, [
                'message' => 'CPF é obrigatório'
            ]);
        }

        if (!($usuarioDto->getNome())) {
            array_push($erros, [
                'message' => 'Nome é obrigatório'
            ]);
        }

        if (!($usuarioDto->getEmail())) {
            array_push($erros, [
                'message' => 'Email é obrigatório'
            ]);
        }

        if (!($usuarioDto->getSenha())) {
            array_push($erros, [
                'message' => 'Senha é obrigatório'
            ]);
        }

        if (!($usuarioDto->getTelefone())) {
            array_push($erros, [
                'message' => 'Telefone é obrigatório'
            ]);
        }

        if (count($erros) > 0) {
            return $this->json($erros, 422);
        }

        // valoida se o cpf já está cadastrado
        $usuarioExistente = $usuarioRepository->findByCpf($usuarioDto->getCpf());

        if ($usuarioExistente) {
            return $this->json([
                'message' => 'O CPF informado já está cadastrado'
            ], 409);
        }

        // criar um objeot da entidade usuario
        $usuario = new Usuario();
        $usuario->setCpf($usuarioDto->getCpf());
        $usuario->setNome($usuarioDto->getNome());
        $usuario->setEmail($usuarioDto->getEmail());
        $usuario->setSenha($usuarioDto->getSenha());
        $usuario->setTelefone($usuarioDto->getTelefone());

        // criar registro na tb usuario
        $entitymanager->persist($usuario);
        $entitymanager->flush();

        //instanciar o objeto conta

        $conta = new Conta();
        $numeroConta = preg_replace('/\D/', '', uniqid());
        $numeroConta = rand(1, 99999);
        $conta->setNumero($numeroConta);
        $conta->setSaldo('0');
        $conta->setUsuario($usuario);

        //criar registro na tb conta

        $entitymanager->persist($conta);
        $entitymanager->flush();

        // retornar os dados de usuario e conta

        $usuarioContaDto = new UsuarioContaDto();
        $usuarioContaDto->setId($usuario->getId());
        $usuarioContaDto->setCpf($usuario->getCpf());
        $usuarioContaDto->setNome($usuario->getNome());
        $usuarioContaDto->setEmail($usuario->getEmail());
        $usuarioContaDto->setTelefone($usuario->getTelefone());
        $usuarioContaDto->setSaldo($conta->getSaldo());
        $usuarioContaDto->setNumeroConta($conta->getNumero());

        return $this->json($usuarioContaDto, status: 201);
    }

    #[Route('/usuarios/{id}', name: 'usuarios_buscar', methods: ['GET'])]
    public function buscarPorId(
        int $id,
        ContaRepository $contaRepository
    ) {
        $conta = $contaRepository->findByUsuarioId($id);

        if (! $conta) {
            return $this->json([
                'message' => 'Usuário não encontrado'
            ], status: 404);
        }

        $usuarioContaDto = new UsuarioContaDto();
        $usuarioContaDto->setId($conta->getUsuario()->getId());
        $usuarioContaDto->setCpf($conta->getUsuario()->getCpf());
        $usuarioContaDto->setNome($conta->getUsuario()->getNome());
        $usuarioContaDto->setEmail($conta->getUsuario()->getEmail());
        $usuarioContaDto->setTelefone($conta->getUsuario()->getTelefone());
        $usuarioContaDto->setSaldo($conta->getSaldo());
        $usuarioContaDto->setNumeroConta($conta->getNumero());

        return $this->json($usuarioContaDto, status: 201);

    }
}
