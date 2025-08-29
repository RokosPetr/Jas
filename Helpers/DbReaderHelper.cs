using Microsoft.EntityFrameworkCore;
using System;
using System.Collections.Generic;
using System.Data.Common;
using System.Reflection;

namespace Jas.Helpers
{
    public static class DbReaderHelper
    {
        /// <summary>
        /// Převod DbDataReader na seznam objektů typu T.
        /// Sloupce v readeru musí odpovídat názvům vlastností v T (case-insensitive).
        /// </summary>
        public static List<T> Translate<T>(this DbContext context, DbDataReader reader) where T : new()
        {
            var result = new List<T>();
            var props = typeof(T).GetProperties(BindingFlags.Public | BindingFlags.Instance);

            var columnNames = new HashSet<string>(StringComparer.OrdinalIgnoreCase);
            for (int i = 0; i < reader.FieldCount; i++)
                columnNames.Add(reader.GetName(i));

            while (reader.Read())
            {
                var obj = new T();
                foreach (var prop in props)
                {
                    if (!columnNames.Contains(prop.Name)) continue;

                    var value = reader[prop.Name];
                    if (value == DBNull.Value) continue;

                    try
                    {
                        prop.SetValue(obj, Convert.ChangeType(value, prop.PropertyType));
                    }
                    catch
                    {
                        // Ignoruj nesoulad typů
                    }
                }

                result.Add(obj);
            }

            return result;
        }

        /// <summary>
        /// Zjistí, zda reader obsahuje daný sloupec.
        /// </summary>
        public static bool HasColumn(this DbDataReader reader, string columnName)
        {
            for (int i = 0; i < reader.FieldCount; i++)
            {
                if (reader.GetName(i).Equals(columnName, StringComparison.OrdinalIgnoreCase))
                    return true;
            }
            return false;
        }
    }
}
