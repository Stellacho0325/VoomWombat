document.getElementById('searchBox').addEventListener('input', function() {
    const inputValue = this.value;
    if (inputValue.length > 1) { // Trigger only if more than one character is typed
        fetch(`suggest.php?query=${inputValue}`)
        .then(response => response.json())
        .then(data => {
            const suggestions = data.map(item => `<div>${item}</div>`).join('');
            document.getElementById('suggestions').innerHTML = suggestions;
        });
    }
});

document.getElementById('searchBox').addEventListener('input', function() {
    const input = this.value;
    const recentSearchesContainer = document.getElementById('recentSearches');
    if (input.length > 0) {
        fetch(`suggestions.php?query=${encodeURIComponent(input)}`)
            .then(response => response.json())
            .then(data => {
                let suggestionsHTML = '';
                data.forEach(item => {
                    suggestionsHTML += `<div onclick="setSearch('${item}')">${item}</div>`;
                });
                recentSearchesContainer.innerHTML = suggestionsHTML;
                recentSearchesContainer.style.display = 'block';
            });
    } else {
        showRecentSearches(); // Show recent searches if the input is empty
    }
});

function setSearch(value) {
    const searchBox = document.getElementById('searchBox');
    searchBox.value = value;
    searchBox.form.submit();
}