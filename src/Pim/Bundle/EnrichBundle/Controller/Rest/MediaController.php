<?php

namespace Pim\Bundle\EnrichBundle\Controller\Rest;

use Akeneo\Component\FileStorage\PathGeneratorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Media controller
 *
 * @author    Julien Sanchez <julien@akeneo.com>
 * @copyright 2015 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class MediaController
{
    /** @var ValidatorInterface */
    protected $validator;

    /** @var PathGeneratorInterface */
    protected $pathGenerator;

    /** @var string */
    protected $uploadDir;

    /** @var bool */
    protected $isDebug;

    /**
     * @param ValidatorInterface $validator
     * @param PathGeneratorInterface $pathGenerator
     * @param string $uploadDir
     */
    public function __construct(ValidatorInterface $validator, PathGeneratorInterface $pathGenerator, $uploadDir, bool $isDebug)
    {
        $this->validator = $validator;
        $this->pathGenerator = $pathGenerator;
        $this->uploadDir = $uploadDir;
        $this->isDebug = $isDebug;
    }

    /**
     * Post a new media and return it's temporary identifier
     *
     * @param Request $request
     *
     * @return JsonResponse
     */
    public function postAction(Request $request)
    {
        /** @var \Symfony\Component\HttpFoundation\File\UploadedFile $file */
        $file = $request->files->get('file');
        $violations = $this->validator->validate($file);

        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[$violation->getPropertyPath()] = [
                    'message' => $violation->getMessage(),
                    'invalid_value' => $violation->getInvalidValue()
                ];
            }

            return new JsonResponse($errors, 400);
        }

        $pathData = $this->pathGenerator->generate($file);

        try {
            $movedFile = $file->move(
                $this->uploadDir . DIRECTORY_SEPARATOR . $pathData['path'] . DIRECTORY_SEPARATOR . $pathData['uuid'],
                $file->getClientOriginalName()
            );
        } catch (FileException $e) {
            if ($this->isDebug) {
                throw $e;
            }

            return new JsonResponse("Error uploading file, " . $e->getMessage(), 400);
        }

        return new JsonResponse(
            [
                'originalFilename' => $file->getClientOriginalName(),
                'filePath' => $movedFile->getPathname()
            ]
        );
    }
}
