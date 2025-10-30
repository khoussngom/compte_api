<?php

/**
 * Centralized Swagger/OpenAPI annotations for Compte-related endpoints.
 *
 * This file contains only documentation blocks scanned by swagger-php
 * so controllers remain clean. Keep paths in sync with route definitions.
 */

/**
 * @OA	ag(
 *   name="Comptes",
 *   description="Opérations liées aux comptes"
 * )
 */

/**
 * Archive endpoint (was previously on CompteController::archive)
 * @OA\Post(
 *     path="/api/v1/comptes/{id}/archive",
 *     summary="Archive un compte au lieu de le supprimer",
 *     tags={"Comptes"},
 *     @OA\Parameter(name="id", in="path", required=true, description="Identifiant du compte : UUID (id) ou numéro de compte (numero_compte)", @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Compte archivé (job en file pour déplacement vers la base tampon)", @OA\JsonContent(@OA\Property(property="id", type="string"), @OA\Property(property="numeroCompte", type="string"), @OA\Property(property="movedToBuffer", type="string", example="queued"))),
 *     @OA\Response(response=404, description="Compte introuvable"),
 *     @OA\Response(response=400, description="Requête invalide"),
 *     @OA\Response(response=500, description="Erreur serveur lors de l'archivage")
 * )
 */

/**
 * Mes comptes (was CompteController::mesComptes)
 * @OA\Get(
 *     path="/api/v1/comptes/mes-comptes",
 *     summary="Liste les comptes du client connecté",
 *     tags={"Comptes"},
 *     @OA\Response(response=200, description="Liste des comptes du client")
 * )
 */

/**
 * Show by numero (was CompteController::show / showByNumero)
 * @OA\Get(
 *     path="/api/v1/comptes/{numero}",
 *     summary="Détail d’un compte par numéro",
 *     tags={"Comptes"},
 *     @OA\Parameter(name="numero", in="path", required=true, @OA\Schema(type="string")),
 *     @OA\Response(response=200, description="Détail du compte")
 * )
 */

/**
 * Bloquer V2 (was CompteController::bloquerV2)
 * @OA\Post(
 *     path="/api/v1/comptes/{compte}/bloquer-v2",
 *     summary="Bloquer un compte (motif + durée)",
 *     tags={"Comptes"},
 *     @OA\Parameter(name="compte", in="path", required=true, @OA\Schema(type="string")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(required={"motif","duree","unite"}, @OA\Property(property="motif", type="string"), @OA\Property(property="duree", type="integer"), @OA\Property(property="unite", type="string"))),
 *     @OA\Response(response=200, description="Compte bloqué / données renvoyées")
 * )
 */

/**
 * Debloquer (was CompteController::debloquer)
 * @OA\Post(
 *     path="/api/v1/comptes/{compte}/debloquer",
 *     summary="Débloquer un compte (motif)",
 *     tags={"Comptes"},
 *     @OA\Parameter(name="compte", in="path", required=true, @OA\Schema(type="string")),
 *     @OA\RequestBody(required=true, @OA\JsonContent(required={"motif"}, @OA\Property(property="motif", type="string"))),
 *     @OA\Response(response=200, description="Compte débloqué / données renvoyées")
 * )
 */

// You can add more path-level or schema annotations here if needed.
