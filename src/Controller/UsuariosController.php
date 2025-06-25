<?php

namespace App\Controller;

use App\Dto\UsuarioDto;
use App\Entity\Conta;
use App\Entity\Usuario;
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
        #[MapRequestPayload(acceptFormat:'json')]
        UsuarioDto $usuarioDto,

        EntityManagerInterface $entitymanager,

        UsuarioRepository $usuarioRepository

    ): JsonResponse
    {
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
        // $numeroConta = preg_replace('/\D/', '', uniqid());
        $numeroConta = rand(1,99999);
        $conta->setNumero($numeroConta);
        $conta->setSaldo('0');
        $conta->setUsuario($usuario);

        //criar registro na tb conta

        $entitymanager ->persist($conta);
        $entitymanager ->flush();

        
        return $this->json($usuario);
    }
}
