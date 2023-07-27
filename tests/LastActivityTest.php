<?php

use Spatie\Activitylog\Test\Models\ArticleWithLastActivity;

it('gets the last activity for the model', function () {
    $article = ArticleWithLastActivity::first();
    $article->name = 'Title change';
    $article->save();

    $article->load('lastActivity');

    expect($article->lastActivity)
        ->description->toBe('updated')
        ->subject_type->toBe(ArticleWithLastActivity::class);

    expect($article->lastActivity->changes->toArray())
        ->toEqual(
            [
                'attributes' => [
                    'name' => 'Title change',
                ],
                'old' => [
                    'name' => 'name 1',
                ],
            ],
        );
});

it('gets the created activity for the model', function () {
    $article = ArticleWithLastActivity::create(['name' => 'New article']);

    $createdArticle = ArticleWithLastActivity::with(['lastActivity'])->find($article->id);

    expect($createdArticle->lastActivity)
        ->description->toBe('created')
        ->subject_type->toBe(ArticleWithLastActivity::class);
});
