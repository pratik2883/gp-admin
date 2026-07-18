String getInitials(String name) {
  if (name.isEmpty) return '??';
  
  final words = name.trim().split(RegExp(r'\s+'));
  if (words.length == 1) {
    return words.first.substring(0, words.first.length > 1 ? 2 : 1).toUpperCase();
  }
  
  final firstInitial = words.first[0];
  final lastInitial = words.last[0];
  
  return (firstInitial + lastInitial).toUpperCase();
}
