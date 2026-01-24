<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Entity\User;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Entity\Specialty;
use Forumify\Milhq\Entity\Status;
use Forumify\Milhq\Entity\Unit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Asset\Packages;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class UserType extends AbstractType
{
    public function __construct(
        private readonly Packages $packages,
    ) {
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Soldier::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Soldier|null $user */
        $user = $options['data'] ?? null;

        $builder->add('name', TextType::class);

        if ($user?->getUser() === null) {
            $builder->add('user', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'username',
                'class' => User::class,
                'required' => false,
            ]);
        } else {
            $builder->add('forumUser', TextType::class, [
                'data' => $user->getUser()->getDisplayName(),
                'disabled' => true,
                'mapped' => false,
            ]);
        }

        $builder
            ->add('rank', EntityType::class, [
                'choice_label' => 'name',
                'class' => Rank::class,
                'help' => 'milhq.admin.users.edit.rank_help',
                'required' => false,
            ])
            ->add('createdAt', DateType::class, [
                'help' => 'milhq.admin.users.edit.created_at_help',
                'widget' => 'single_text',
            ])
            ->add('steamId', NumberType::class, [
                'help' => 'milhq.admin.users.edit.steam_id_help',
                'help_html' => true,
                'required' => false,
            ])
            // assignment
            ->add('specialty', EntityType::class, [
                'choice_label' => 'name',
                'class' => Specialty::class,
                'disabled' => true,
                'required' => false,
            ])
            ->add('status', EntityType::class, [
                'choice_label' => 'name',
                'class' => Status::class,
                'disabled' => true,
                'required' => false,
            ])
            ->add('position', EntityType::class, [
                'choice_label' => 'name',
                'class' => Position::class,
                'disabled' => true,
                'required' => false,
            ])
            ->add('unit', EntityType::class, [
                'choice_label' => 'name',
                'class' => Unit::class,
                'disabled' => true,
                'required' => false,
            ])
            ->add('secondaryAssignmentRecords', HiddenType::class, [
                'mapped' => false,
            ])
            // uniform
            ->add('newUniform', FileType::class, [
                'attr' => [
                    'preview' => $user?->getUniform()
                        ? $this->packages->getUrl($user->getUniform(), 'milhq.asset')
                        : null,
                ],
                'constraints' => [
                    new Assert\Image(
                        maxSize: '1M',
                    ),
                ],
                'label' => 'Uniform',
                'mapped' => false,
                'required' => false,
            ])
            ->add('newSignature', FileType::class, [
                'attr' => [
                    'preview' => $user?->getSignature()
                        ? $this->packages->getUrl($user->getSignature(), 'milhq.asset')
                        : null,
                ],
                'constraints' => [
                    new Assert\Image(
                        maxSize: '1M',
                    ),
                ],
                'label' => 'Signature',
                'mapped' => false,
                'required' => false,
            ])
        ;
    }
}
