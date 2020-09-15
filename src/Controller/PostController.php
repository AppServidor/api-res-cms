<?php

namespace App\Controller;

use App\Entity\Post;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class PostController extends AbstractController
{
    private function resJson($data)
    {
        // Serializar datos con servicios de serializacion
        $json = $this->get('serializer')->serialize($data, 'json');

        //Response con hhtp foundation
        $response = new Response();

        //Asignarle contenido de la respuesta
        $response->setContent($json);

        //indicar el contenido de la respuesta
        $response->headers->set('Content-Type', 'application/json');

        //devolver la respuesta
        return $response;
    }

    public function index(PostRepository $postRepository)
    {
        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'No se han podido recuperar los post'
        );
        $posts = $postRepository->findAll();
        return $this->resJson($posts);
    }

    public function show( PostRepository $postRepository){
        $post = $postRepository->find(1);
        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'No se han podido recuperar los post'
        );

        if(is_object($post)){
            $data = array(
                'status' => 'succes',
                'code' => 200,
                'message' => 'Post recuperado correctamente',
                'json' => $post
            );
            return $this->resJson($data);
        }else{
            return $this->resJson($data);
        }
    }
}
