# instagram
Library for grab a media by hashtag

# Usage
<pre>
$insta = new Instagram;
$insta->setMaxData(20)
      ->setHashTag('like4like')
      ->retrieve();
</pre>
<br />
<b>Optional method can used</b>
<li>setMaxData();</li>
<li>mostLiked();</li>
<li>orderByLiked;</li>
<br />

<h2>Usage for get total of likes by Instagram ID</h2>
<pre>
$likes = new Instagram;
$likes->getLikesByInstagramId('INSTAGRAM ID')
      ->retrieve();
</pre>
<br />
EOF
