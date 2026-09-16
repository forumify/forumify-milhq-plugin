<?php

declare(strict_types=1);

namespace Forumify\Milhq\Admin\Form;

use Forumify\Core\Entity\User;
use Forumify\Core\Form\UploadType;
use Forumify\Milhq\Entity\Soldier;
use Forumify\Milhq\Entity\Position;
use Forumify\Milhq\Entity\Rank;
use Forumify\Milhq\Entity\Specialty;
use Forumify\Milhq\Entity\Status;
use Forumify\Milhq\Entity\Unit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class SoldierType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Soldier::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class)
            ->add('user', EntityType::class, [
                'autocomplete' => true,
                'choice_label' => 'username',
                'class' => User::class,
                'required' => false,
            ])
            ->add('rank', EntityType::class, [
                'choice_label' => 'name',
                'class' => Rank::class,
                'help' => 'milhq.admin.soldiers.edit.rank_help',
                'required' => false,
            ])
            ->add('createdAt', DateType::class, [
                'help' => 'milhq.admin.soldiers.edit.created_at_help',
                'widget' => 'single_text',
            ])
            ->add('steamId', NumberType::class, [
                'help' => 'milhq.admin.soldiers.edit.steam_id_help',
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
            ->add('uniform', UploadType::class, [
                'accept' => 'image/*',
                'asset_package' => 'milhq.asset',
                'file_constraints' => [new Assert\Image(maxSize: '1M')],
                'filesystem' => 'milhq_asset.storage',
                'label' => 'Uniform',
                'required' => false,
            ])
            ->add('signature', UploadType::class, [
                'accept' => 'image/*',
                'asset_package' => 'milhq.asset',
                'file_constraints' => [new Assert\Image(maxSize: '1M')],
                'filesystem' => 'milhq_asset.storage',
                'label' => 'Signature',
                'required' => false,
            ])
        ;
    }
}
