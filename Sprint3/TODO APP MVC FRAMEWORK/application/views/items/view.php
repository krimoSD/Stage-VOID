<h2><?php echo htmlspecialchars($todo['Item']['item_name']); ?></h2>

<a class="big" href="../../../items/delete/<?php echo $todo['Item']['id']; ?>">
    <span class="item">
        &#x1F5D1; Delete this item
    </span>
</a>

<br><br>
<a href="../../../items/viewall">← Back to all items</a>
