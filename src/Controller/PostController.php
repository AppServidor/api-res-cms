<?php

namespace App\Controller;

use App\Entity\Post;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;
use App\Services\JwtAuth;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Repository\PostRepository;
use Symfony\Component\Validator\Constraints\Date;

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


    public function show($slug, PostRepository $postRepository,JwtAuth $jwt_auth, Request $request){

        $token = $request->headers->get('Authorization');
        $auth_check = $jwt_auth->checkToken($token);

        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'No se han podido recuperar los post'
        );

        if($auth_check){

            $identity = $jwt_auth->checkToken($token, true); 

            $post = $postRepository->findOneBy([
                'slug'=>$slug
                ]);

            $data = array(
                'status' => 'error',
                'code' => 404,
                'message' => 'No se ha encontrado el post'
            );
    
            if($post && is_object($post) && $identity->sub == $post->getUser()->getId()){
                $data = array(
                    'status' => 'succes',
                    'code' => 200,
                    'message' => 'Post recuperado correctamente',
                    'json' => $post
                );      
            }
        }

        return $this->resJson($data);
    }

    public function new(Request $request, JwtAuth $jwt_auth)
    {

        $data = array(
            'status' => 'error',
            'code' => 400,
            'message' => 'No se ha podido guardar el post'
        );
        //Comprobar que el usuario esta logueado
        $token = $request->headers->get('Authorization');

        $checkToken = $jwt_auth->checkToken($token);

        if($checkToken){
            //Conseguir el entity manager
            $em = $this->getDoctrine()->getManager();

            //Conseguir los datos del usuario identificado
            $identity = $jwt_auth->checkToken($token, true);

            //Respuesta por defecto
            $data = [
                "status" => "error",
                "code" => "400",
                "message" => "Post no creado",
            ];

            //Conseguir el usuario a actualizar completo
            $userRepository = $this->getDoctrine()->getRepository(User::class);
            $user = $userRepository->findOneBy([
                'id' => $identity->sub
            ]);


            //Recoger los datos por post
            $json = $request->get('json', null);
            $params = json_decode($json);

            //Comprobar y validar los datos
            if(!empty($json)){
                $title = (!empty($params->title)) ? $params->title : null;
                $content = (!empty($params->content)) ? $params->content : null;
                $slug = (!empty($params->slug)) ? $params->slug : null;
                $created_at = date_create();
                $updated_at = date_create();
    
                if (!empty($title) && !empty($content) && !empty($slug)) {

                    //Asignar nuevos datos al objeto del usuario
                    $post = new Post();
                    $post->setUser($user);
                    $post->setTitle($title);
                    $post->setContent($content);
                    $post->setCreatedAt($created_at);
                    $post->setUpdatedAt($updated_at);
                    $post->setSlug($slug);

                    //Comprobar duplicados
                    $postRepository = $this->getDoctrine()->getRepository(Post::class);
                    $issetPost = $postRepository->findBy([
                        "slug" => $slug
                    ]);

                    if(count($issetPost) == 0){

                        //Guardar cambios en la base de datos
                        $em->persist($post);
                        $em->flush();

                        $data = [
                            "status" => "success",
                            "code" => "200",
                            "message" => "Post creado",
                            "user" => $post
                        ];
                    }else{
                        $data = [
                            "status" => "error",
                            "code" => "400",
                            "message" => "Slug duplicado",
                        ];
                    }
                }
            }
        }

        return $this->resJson($data);
    }
}
