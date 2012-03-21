function typeset() {
    MathJax.Hub.Queue(["Typeset",MathJax.Hub]);
}

function disqus_config() {
    this.callbacks.onNewComment = [typeset];
    this.callbacks.onInit = [typeset];
    this.callbacks.onPaginate = [typeset];
}
